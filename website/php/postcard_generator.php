<?php

// ****************************************************************
// Artstation2020: Generate a postcard and save it to the database.
// ****************************************************************

include 'shared.php';

//Check we were given an image & character through POST
$img=$_FILES['img'];
if($img['name']=='' || !isset($_POST["gps_lat"]) || !isset($_POST["gps_lon"])) {
	$metadata->error = "The required data was not sent.";
	header('Content-Type: application/json');
	echo json_encode($metadata);
	exit();
}

//Upload image to Imgur
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, 'https://api.imgur.com/3/image.json');
curl_setopt($curl, CURLOPT_TIMEOUT, 30);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Client-ID 1373c00afcd1b08'));
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, array('image' => base64_encode(fread(fopen($img['tmp_name'], "r"), filesize($img['tmp_name'])))));
$out = curl_exec($curl);
curl_close ($curl);

//Get URL from Imgur
$pms = json_decode($out,true);
if ($pms['success'] != 1) {
	$metadata->error = "Error code ".$pms["status"]."... ".$pms["data"]["error"];
	header('Content-Type: application/json');
	echo json_encode($metadata);
	exit();
}
$metadata->image_url = $pms['data']['link'];
$metadata->image_id = $pms['data']['id'];

//Setup for Google Vision API (update this with your path to JSON)
putenv('GOOGLE_APPLICATION_CREDENTIALS='.$GOOGLE_API_PATH);
require __DIR__ . '/../vendor/autoload.php';
use Google\Cloud\Vision\V1\ImageAnnotatorClient;

//Process image through the Vision API
$imageAnnotator = new ImageAnnotatorClient();
$image = file_get_contents($metadata->image_url);
$response = $imageAnnotator->labelDetection($image);
$labels = $response->getLabelAnnotations();

//Collect labels
$metadata->autogen_labels = array(); 
if ($labels) {
    foreach ($labels as $label) {
		$this_label = new stdClass();
		$this_label->word = strtolower($label->getDescription());
		$this_label->synonyms = json_decode(file_get_contents("http://thesaurus.altervista.org/thesaurus/v1?key=5YGNz4H1pUtmz8nUhhHj&word=".explode(" ", $this_label->word)[0]."&language=en_US&output=json"));
		$this_label->synonyms = explode("|", explode(" (", $this_label->synonyms->{"response"}[rand(0, count($this_label->synonyms->{"response"})-1)]->{"list"}->{"synonyms"})[0]);
		$temp_synonym_limiter = array();
		for ($i = 0; $i < count($this_label->synonyms); $i++) {
			if (count($temp_synonym_limiter) == 3) break;
			if ($this_label->synonyms[$i] == "") continue;
			array_push($temp_synonym_limiter, $this_label->synonyms[$i]);
		}
		$this_label->synonyms = $temp_synonym_limiter;
		$this_label->type = json_decode(file_get_contents($BASE_URL.dirname($_SERVER["PHP_SELF"])."/get_wordtype.php?word=".str_replace(" ", "%20", $this_label->word)))->partOfSpeech;
		array_push($metadata->autogen_labels, $this_label);
    }
}
$metadata->custom_labels = array(); 

//Try and find a character to match the GPS location
$characterData = json_decode(file_get_contents($BASE_URL.dirname($_SERVER["PHP_SELF"]).'/get_characters.php'));
$shortestDist = 99999999999;
$shortestDistID = -1;
$metadata->location->lat = -1;
$metadata->location->lon = -1;
if ($_POST["gps_lat"] != "null" && $_POST["gps_lon"] != "null") {
	for ($i = 0; $i < count($characterData); $i++) {
		$dist = getDistGPS(
					$characterData[$i]->{'location'}->{'lat'}, $characterData[$i]->{'location'}->{'lon'},
					$_POST["gps_lat"], $_POST["gps_lon"]
				);
		if ($dist < 0) $dist *= -1;
		if ($dist < $shortestDist) {
			$shortestDist = $dist;
			$shortestDistID = $i;
		}
	}
	$metadata->location->lat = $_POST["gps_lat"];
	$metadata->location->lon = $_POST["gps_lon"];
}
$metadata->character_is_random = ($shortestDistID == -1);
if ($shortestDistID == -1) $shortestDistID = rand(0, count($characterData) - 1); //If no GPS match, pick random.
$metadata->character = $characterData[$shortestDistID];

//Generate a soundscape
$soundscapeData = json_decode(
	file_get_contents('http://ec2-52-27-55-90.us-west-2.compute.amazonaws.com/process?save=1', false, stream_context_create([
		'http' => [
			'method' => 'POST',
			'header'  => "Content-type: application/x-www-form-urlencoded",
			'content' => http_build_query([
				'link' => $metadata->image_url
			])
		]
	]))
);
$metadata->soundscape_url = "https://imaginary-soundscape.s3-ap-northeast-1.amazonaws.com/sounds/".$soundscapeData->{'sound_id'}.".mp3";
$metadata->soundscape_id = $soundscapeData->{'sound_id'};
$metadata->using_custom_soundscape = false;

//Enter placeholders that will be replaced when words are selected
$metadata->haiku = "";
$metadata->poem = "";
$metadata->poem_author = "";

//Save to database and return ID
$connection = mysqli_connect($hostname, $username, $password, $database);
mysqli_query($connection, "INSERT INTO postcard_shares (json_content) VALUES ('".json_encode($metadata)."')");
$metadata->postcard_id = mysqli_insert_id($connection); //Should probably do some error handling here
mysqli_close($connection);

//Display results
$metadata->error = "N/A";
header('Content-Type: application/json');
echo json_encode($metadata);
?>
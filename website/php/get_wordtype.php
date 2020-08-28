<?php

// ****************************************************
// Artstation2020: Get the type information for a word.
// ****************************************************

$dummy->word = "";
$dummy->partOfSpeech = "";

header('Content-Type: application/json');

if (!isset($_GET["word"]) || $_GET["word"] == "") {
	echo json_encode($dummy);
	exit();
}

include 'shared.php';

$curl = curl_init();
curl_setopt_array($curl, array(
	CURLOPT_URL => "https://wordsapiv1.p.rapidapi.com/words/".str_replace(" ", "%20", $_GET["word"])."/partOfSpeech",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => array(
		"x-rapidapi-host: wordsapiv1.p.rapidapi.com",
		"x-rapidapi-key: ".$RAPIDAPI_KEY
	),
));
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
	echo json_encode($dummy);
} else {
	echo $response;
}
?>
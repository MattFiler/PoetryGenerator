<?php

// ************************************************************
// Artstation2020: Update the postcard words and return a poem.
// ************************************************************

include 'shared.php';
ini_set('default_socket_timeout', 60);

//Check we were given an ID for the database & selected words through POST
if(!isset($_POST["id"]) || !isset($_POST["word_1"]) || !isset($_POST["word_2"]) || !isset($_POST["word_3"])) {
	$metadata->error = "The required data was not sent.";
	header('Content-Type: application/json');
	echo json_encode($metadata);
	exit();
}

//Pull all required info from database
$connection = mysqli_connect($hostname, $username, $password, $database);
$postcardDB = mysqli_query($connection, "SELECT * FROM postcard_shares");
$soundscapeDB = mysqli_query($connection, "SELECT * FROM custom_soundscapes");
mysqli_close($connection);

//Grab the one that matches our ID and return it
while($row = mysqli_fetch_assoc($postcardDB)) {
	if ($row["id"] == $_POST["id"]) {
		//Format data
		$metadata = json_decode($row["json_content"]);
		$metadata->postcard_id = $_POST["id"];
		$metadata->error = "N/A";
		
		//Add new custom labels
		$metadata->custom_labels = array(strtolower($_POST["word_1"]), strtolower($_POST["word_2"]), strtolower($_POST["word_3"])); 
		
		//Generate a haiku
		$labelToUse = "default";
		foreach ($metadata->custom_labels as $label) {
			if (strpos($label, " ") !== false) continue;
			$labelToUse = $label;
			break;
		}
		//$metadata->haiku = json_decode(file_get_contents($BASE_URL.'python/haiku_generator.py?keyword='.$labelToUse));
		
		//Sort autogen labels into word types
		$nouns = array();
		$verbs = array();
		$adjectives = array();
		$adverbs = array();
		foreach ($metadata->autogen_labels as $word) {
			if (count($word->type) == 0) continue;
			if ($word->type[0] == "noun") array_push($nouns, $word->word);
			if ($word->type[0] == "verb") array_push($verbs, $word->word);
			if ($word->type[0] == "adjective") array_push($adjectives, $word->word);
			if ($word->type[0] == "adverb") array_push($adverbs, $word->word);
		}
		
		//Generate a poem
		$selectedAuthor = $metadata->character->authors[rand(0, count($metadata->character->authors)-1)];
		$metadata->poem_raw = json_decode(file_get_contents($BASE_URL.'python/generator/generator.py?artist='.str_replace(" ", "%20", $selectedAuthor).'&loop_timeout=40&num_of_lines='.rand (5, 7)));
		$metadata->poem_author = $selectedAuthor;
		
		//Analyse poem and find word replacements based on autogen labels
		$cleanPoem = array();
		foreach ($metadata->poem_raw as $poemLine) {
			$poemLineWords = explode(" ", preg_replace("/[^ \w]+/", "", $poemLine));
			foreach ($poemLineWords as $thisWord) {
				array_push($cleanPoem, $thisWord);
			}
		}
		$wordReplacements = array();
		foreach ($cleanPoem as $word) {
			$wordTypes = json_decode(file_get_contents($BASE_URL.dirname($_SERVER["PHP_SELF"])."/get_wordtype.php?word=".str_replace(" ", "%20", $word)))->partOfSpeech;
			if (count($wordTypes) == 0) continue;
			if (strlen($word) <= 3) continue;
			$replacement = new stdClass();
			$replacement->original_word = $word;
			$replacement->new_word = "";
			if ($wordTypes[0] == "noun" && count($nouns) != 0) $replacement->new_word = $nouns[rand(0, count($nouns)-1)];
			if ($wordTypes[0] == "verb" && count($verbs) != 0) $replacement->new_word = $verbs[rand(0, count($verbs)-1)];
			if ($wordTypes[0] == "adjective" && count($adjectives) != 0) $replacement->new_word = $adjectives[rand(0, count($adjectives)-1)];
			if ($wordTypes[0] == "adverb" && count($adverbs) != 0) $replacement->new_word = $adverbs[rand(0, count($adverbs)-1)];
			if ($replacement->new_word == "") continue;
			$shouldAdd = true;
			foreach ($wordReplacements as $existingReplacement) {
				if ($existingReplacement->new_word == $replacement->new_word) {
					$shouldAdd = false;
					break;
				}
				if ($existingReplacement->original_word == $replacement->original_word) {
					$shouldAdd = false;
					break;
				}
			}
			if ($shouldAdd) array_push($wordReplacements, $replacement);
		}
		
		//Sanity check the poem for dodgy characters
		$fixedPoem = array();
		foreach ($metadata->poem_raw as $poemLine) {
			array_push($fixedPoem, str_replace("'", "&apos;", $poemLine));
		}
		$metadata->poem_raw = $fixedPoem;
		
		//Replace the words
		$metadata->poem = $metadata->poem_raw;
		foreach ($wordReplacements as $replacement) {
			$metadata->poem = preg_replace('/\b'.$replacement->original_word.'\b/u', $replacement->new_word, $metadata->poem);
		}
		
		//Check for custom soundscape overrides that match
		$allLabels = $metadata->custom_labels;
		foreach ($metadata->autogen_labels as $label) {
			array_push($allLabels, $label->word);
		}
		$overrideURL = "";
		while($rowS = mysqli_fetch_assoc($soundscapeDB)) {
			$metadataS = json_decode($rowS["json_content"]);
			foreach ($metadataS->matching_words as $word) {
				foreach ($allLabels as $label) {
					if (strtolower($word) == strtolower($label)) {
						$overrideURL = $metadataS->sound_url;
						break;
					}
				}
				if ($overrideURL != "") break;
			}
			if ($overrideURL != "") break;
		}
		if ($overrideURL != "") {
			$metadata->soundscape_url = $overrideURL;
			$metadata->using_custom_soundscape = true;
		}
		
		//Update entry in database
		$connection = mysqli_connect($hostname, $username, $password, $database);
		mysqli_query($connection, "UPDATE postcard_shares SET json_content='".json_encode($metadata)."' WHERE id=".$row["id"]);
		mysqli_close($connection);
		
		//Show updated content
		header('Content-Type: application/json');
		echo json_encode($metadata);
		exit();
	}
}

//Must not exist if we got this far
$metadata->error = "Invalid ID given.";
header('Content-Type: application/json');
echo json_encode($metadata);
?>
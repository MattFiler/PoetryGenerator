<?php

// **************************************
// Artstation2020: Save a new soundscape.
// **************************************

include 'shared.php';

//Check data
if(!isset($_POST["keywords"]) || !isset($_POST["url"])) {
	$result->error = "The required data was not sent.";
	header('Content-Type: application/json');
	echo json_encode($result);
	exit();
}

//Combine data
$soundscapeData->sound_url = $_POST["url"];
$soundscapeData->matching_words = explode(",", $_POST["keywords"]);

//Update entry in database
$connection = mysqli_connect($hostname, $username, $password, $database);
mysqli_query($connection, "INSERT INTO custom_soundscapes (json_content) VALUES ('".json_encode($soundscapeData)."')");
mysqli_close($connection);

//Print out
header('Content-Type: application/json');
$result->error = "N/A";
echo json_encode($result);
?>
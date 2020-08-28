<?php

// *********************************************************************
// Artstation2020: Update character by index (assuming ID is index + 1).
// *********************************************************************

include 'shared.php';

//Check data
if(!isset($_POST["index"]) || !isset($_POST["era"]) || !isset($_POST["name"]) || !isset($_POST["authors"]) || !isset($_POST["img_url"]) || !isset($_POST["lat"]) || !isset($_POST["lon"])) {
	$result->error = "The required data was not sent.";
	header('Content-Type: application/json');
	echo json_encode($result);
	exit();
}

//Combine data
$characterData->era = $_POST["era"];
$characterData->name = $_POST["name"];
$characterData->authors = explode(",", $_POST["authors"]);
$characterData->img_url = $_POST["img_url"];
$characterData->location->lat = $_POST["lat"];
$characterData->location->lon = $_POST["lon"];

//Update entry in database
$connection = mysqli_connect($hostname, $username, $password, $database);
$result->error = "N/A";
if (!mysqli_query($connection, "UPDATE character_profiles SET json_content='".json_encode($characterData)."' WHERE id=".($_POST["index"]+1))) {
	$result->error = mysqli_error($con);
}
mysqli_close($connection);

//Print out
header('Content-Type: application/json');
echo json_encode($result);
?>
<?php

// *****************************************************
// Artstation2020: List all soundscapes in the database.
// *****************************************************

include 'shared.php';

//Pull all soundscapes from database
$connection = mysqli_connect($hostname, $username, $password, $database);
$fromDB = mysqli_query($connection, "SELECT * FROM custom_soundscapes");
mysqli_close($connection);

//Add them to an array
$soundscapes = array();
while($row = mysqli_fetch_assoc($fromDB)) {
	$thisSoundscape = new stdClass();
	$thisSoundscape->id = $row["id"];
	$thisSoundscape->data = json_decode($row["json_content"]);
	array_push($soundscapes, $thisSoundscape);
}

//Print out
header('Content-Type: application/json');
echo json_encode($soundscapes);
?>
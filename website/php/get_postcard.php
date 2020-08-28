<?php

// ****************************************************
// Artstation2020: Recall a postcard from the database.
// ****************************************************

include 'shared.php';

//Check we were given a postcard ID
if(!isset($_GET["id"])) {
	$metadata->error = "No ID given.";
	header('Content-Type: application/json');
	echo json_encode($metadata);
	exit();
}

//Pull all postcards from database
$connection = mysqli_connect($hostname, $username, $password, $database);
$fromDB = mysqli_query($connection, "SELECT * FROM postcard_shares");
mysqli_close($connection);

//Grab the one that matches our ID and return it
while($row = mysqli_fetch_assoc($fromDB)) {
	$metadata = json_decode($row["json_content"]);
	if ($metadata->image_id == $_GET["id"]) {
		$metadata->postcard_id = $_GET["id"];
		$metadata->error = "N/A";
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
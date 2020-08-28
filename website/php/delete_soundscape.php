<?php

// ******************************************
// Artstation2020: Delete a soundscape by ID.
// ******************************************

include 'shared.php';

//Check data
if(!isset($_GET["id"])) {
	$result->error = "The required data was not sent.";
	header('Content-Type: application/json');
	echo json_encode($result);
	exit();
}

//Update entry in database
$connection = mysqli_connect($hostname, $username, $password, $database);
$result->error = "N/A";
if (!mysqli_query($connection, "DELETE FROM custom_soundscapes WHERE id=".$_GET["id"])) {
	$result->error = mysqli_error($con);
}
mysqli_close($connection);

//Print out
header('Content-Type: application/json');
echo json_encode($result);
?>
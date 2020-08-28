<?php

// **************************************************
// Artstation2020: Display all character information.
// **************************************************

include 'shared.php';

//Pull all characters from database
$connection = mysqli_connect($hostname, $username, $password, $database);
$fromDB = mysqli_query($connection, "SELECT * FROM character_profiles");
mysqli_close($connection);

//Display them
$characters = array();
while($row = mysqli_fetch_assoc($fromDB)) {
	if (isset($_GET["id"]) && $row["id"] != $_GET["id"]) continue;
	array_push($characters, json_decode($row["json_content"]));
}
header('Content-Type: application/json');
echo json_encode($characters);
?>
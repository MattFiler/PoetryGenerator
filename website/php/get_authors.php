<?php

// ******************************************************
// Artstation2020: Recall all authors able to be trained.
// ******************************************************

include 'shared.php';

//Pull all authors
$pathToAuthors = "../python/generator/input/";
if (dirname($_SERVER["PHP_SELF"]) != "/backend") $pathToAuthors = "../".$pathToAuthors;
$allAuthors = scandir($pathToAuthors);
$filteredAuthors = array();
foreach ($allAuthors as $author) {
	if (strlen($author) <= 4) continue;
	if (substr($author, strlen($author) - 4) != ".txt") continue;
	
	$authorInfo = new stdClass();
	$authorInfo->size = filesize($pathToAuthors.$author);
	$authorInfo->name = substr($author, 0, strlen($author) - 4);
	array_push($filteredAuthors, $authorInfo);
}
header('Content-Type: application/json');
echo json_encode($filteredAuthors);
?>
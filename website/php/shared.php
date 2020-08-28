<?php

// *********************************
// Artstation2020: Shared variables.
// *********************************

$BASE_URL = "";        //Replace this with your URL
$RAPIDAPI_KEY = "";    //Replace this with your RapidAPI key
$GOOGLE_API_PATH = ""; //Replace this with the path to your Google API JSON file
$username = "";        //Replace this with your username
$password = "";        //Replace this with your password
$hostname = "localhost";
$database = "Artstation2020";

/* Get distance between LAT/LON points (thanks: https://www.geodatasource.com/developers/php) */
function getDistGPS($lat1, $lon1, $lat2, $lon2) {
	if (($lat1 == $lat2) && ($lon1 == $lon2)) {
		return 0;
	}
	else {
		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;

		return $miles;
	}
}
?>
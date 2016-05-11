<?php

// This is a prototype Web service to provide visualisations of a 40-digit string that are easier to communicate over the phone
// Written at #chi4good on 7 May 2016

// Uses of this service

// 1. No parameters
// Returns a JSON array of available encoding methods, including name, description and number of parts

// 2. Just a method parameter
// Returns a JSON encoded description of the method, including a number of parts

// 3. A methdod paramete and a fingerprint parameter
// Returns an encoding of the whole fingerprint according using the method as a PNG

// 4. A method parameter and a fingerprint parameter and part parameter
// Returns an encoding of part <part> of the fingerprint using the method as a PNG

// TBD: error handling!

// This bit just handles the first two parts and does redirects for the other two

// Requires a JSON file encoding-methods.json

header('Access-Control-Allow-Origin: *');

$encodingMethods = (array) json_decode(file_get_contents("./encoding-methods.json"));

$fingerprint = $_GET["fingerprint"];
$method = $_GET["method"];
$part = $_GET["part"];

if ($fingerprint == null && $method == null && $part == null) {
	returnEncodingMethods();
}
else if ($fingerprint == null && $method != null) {
	returnEncodingMethodInfo($method);
} else if ($fingerprint != null && $method != null) {
	redirectTo($method, $fingerprint, $part);
}

function returnEncodingMethods() {
	global $encodingMethods;
	header('Content-Type: application/json');
	$methodIds = array_keys($encodingMethods);
	$methods = array();
	foreach ($methodIds as $methodId) {
		$encodingMethod = $encodingMethods[$methodId];
		$method = (Object) [
			'id' => $methodId,
			'name' => $encodingMethod->name,
			'description' => $encodingMethod->description,
			'parts' => $encodingMethod->parts,
			'type' => $encodingMethod->type
		];
		array_push($methods, $method);
	}
	echo json_encode($methods);
}

function returnEncodingMethodInfo($method) {
	global $encodingMethods;

	header('Content-Type: application/json');
	$encodingMethod = $encodingMethods[$method];
	if ($encodingMethod == null)
		$encodingMethod = array(
			"description" => 'Error. No such method as "'.$method.'"',
			"parts" => 0
		);
	echo json_encode($encodingMethod);
	
}

function redirectTo($method, $fingerprint, $part) {
	global $encodingMethods;
	
	$encodingMethod = $encodingMethods[$method];

	$targetUrl = $encodingMethod->url;
	if (strpos($targetUrl, '?') === false)
		$targetUrl = $targetUrl . '?';
	else
		$targetUrl = $targetUrl . '&';
	$targetUrl = $targetUrl.'fingerprint='.urlencode($fingerprint);
	if ($part != null)
		$targetUrl = $targetUrl.'&part='.$part;
	header('Location: '.$targetUrl);
	die();
}

?>

<?php

ob_start();

require '../vendor/autoload.php';

// https://console.developers.google.com/iam-admin/serviceaccounts?folder=&organizationId=&project=babydol-upheld-archway-205100
define("PROJECT_ID", 'babydol-upheld-archway-205100');
define("PROJECT_CREDENTIALS_FILE", '../config/babydol-upheld-archway-205100-39ddca1906c1.json');

// Create one or more API keys at https://console.developers.google.com/apis/credentials
define("SEARCH_API_KEY", '../config/apikey-token.json');

// langauge for text recognizion
define("LANGUAGE_CODE", 'de-DE');

putenv('GOOGLE_APPLICATION_CREDENTIALS=' . PROJECT_CREDENTIALS_FILE);

// use Google\Cloud\Core\ServiceBuilder;
use h4de5\BabyDol\Controller;

$controller = new Controller('..');
$result = $controller->handleRequest($_REQUEST['action']);

$buffed_output = ob_get_clean();

header('Access-Control-Allow-Origin: *');

// in case an error occured, and result is empty, output as html
if (!empty($buffed_output) && empty($result)) {
	header("HTTP/1.1 400 Bad Request");
	header('Content-type: text/html');
	echo $buffed_output;
	exit(1);

}

// if no buffered output is there, return json
header('Content-type: application/json;charset=utf-8');

// if its an error, output http error code
if (empty($result) || !is_array($result) || !empty($result['error']) || !empty($buffed_output)) {
	header("HTTP/1.1 400 Bad Request");
}

// include bufferd output into json
if (!empty($buffed_output)) {
	$result['raw_output'] = $buffed_output;
}

echo json_encode($result);

// $client = new Google_Client();
// //$client->setAuthConfig('/volume1/web/dev/babydol/web/'. PROJECT_CREDENTIALS_FILE);

// // $client->addScope(Google_Service_Drive::DRIVE);

// $service = new Google_Service_Customsearch($client);

// $optParams = array("cx"=>self::GCSE_SEARCH_ENGINE_ID);
// $results = $service->cse->listCse("lol cats", $optParams);

// $cloud = new ServiceBuilder();

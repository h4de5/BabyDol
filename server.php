<?php

header("Access-Control-Allow-Origin: *");

require 'vendor/autoload.php';

define("PROJECT_ID", 'babydol-upheld-archway-205100');
define("LANGUAGE_CODE", 'de-DE');

putenv('GOOGLE_APPLICATION_CREDENTIALS=BabyDol-cloud-token-1074b826b0e5.json');

// use Google\Cloud\Core\ServiceBuilder;
use h4de5\BabyDol\Controller;

header('Content-type:application/json;charset=utf-8');

$controller = new Controller();
$result = $controller->handleRequest($_REQUEST['action']);
echo json_encode($result);


    
    


// $client = new Google_Client();
// //$client->setAuthConfig('/volume1/web/dev/babydol/BabyDol-cloud-token-1074b826b0e5.json');

// // $client->addScope(Google_Service_Drive::DRIVE);

// $service = new Google_Service_Customsearch($client);

// $optParams = array("cx"=>self::GCSE_SEARCH_ENGINE_ID);    
// $results = $service->cse->listCse("lol cats", $optParams);



// $cloud = new ServiceBuilder();



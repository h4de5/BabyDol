<?php

header("Access-Control-Allow-Origin: *");

require 'vendor/autoload.php';

define("PROJECT_ID", 'babydol-upheld-archway-205100');
define("LANGUAGE_CODE", 'de-DE');

putenv('GOOGLE_APPLICATION_CREDENTIALS=/volume1/web/dev/babydol/BabyDol-cloud-token-1074b826b0e5.json');

use Google\Cloud\Core\ServiceBuilder;
use Google_Service_Customsearch;
use Google_Client;

use h4de5\BabyDol\Upload;
use h4de5\BabyDol\Speech;
use h4de5\BabyDol\Language;

// TODO filename of uploaded file should not be defined in client
$upload = new Upload('uploads');
// if files are uploaed (ajax request, add them to target file)
if($upload->isUpload('audio-filename', 'audio-blob')) {

    $filepath = $upload->fetch('audio-filename', 'audio-blob');
    $targetFile = $upload->concat($filepath, 'target.pcm');

} else {

    // if no upload, run recognition
    $targetFile = "uploads/target.pcm";
    $speech = new Speech(PROJECT_ID, LANGUAGE_CODE);
    $results = $speech->recognize($targetFile, "LINEAR16", 44100);

    $transcripts = $speech->getTopResults($results);

    echo "Transcriptions: <br />";
    echo implode("<br />". PHP_EOL, $transcripts);
    echo "<br />". PHP_EOL;

    $language = new Language(PROJECT_ID, LANGUAGE_CODE);
    foreach ($transcripts as $idx => $transcript) {

        // $language->analyze($transcript);
        $language->anotate($transcript);
    }
    

    $client = new Google_Client();
    //$client->setAuthConfig('/volume1/web/dev/babydol/BabyDol-cloud-token-1074b826b0e5.json');

    // $client->addScope(Google_Service_Drive::DRIVE);

    $service = new Google_Service_Customsearch($client);

    $optParams = array("cx"=>self::GCSE_SEARCH_ENGINE_ID);    
    $results = $service->cse->listCse("lol cats", $optParams);

}



// $cloud = new ServiceBuilder();



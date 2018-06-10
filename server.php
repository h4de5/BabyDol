<?php

header("Access-Control-Allow-Origin: *");

require 'vendor/autoload.php';

define("PROJECT_ID", 'babydol-upheld-archway-205100');
define("LANGUAGE_CODE", 'de-DE');

putenv('GOOGLE_APPLICATION_CREDENTIALS=BabyDol-cloud-token-1074b826b0e5.json');

use Google\Cloud\Core\ServiceBuilder;
use h4de5\BabyDol\Upload;
use h4de5\BabyDol\Speech;
use h4de5\BabyDol\Language;
use h4de5\BabyDol\Search;


// TODO filename of uploaded file should not be defined in client
$upload = new Upload('uploads');
// if files are uploaed (ajax request, add them to target file)
if($upload->isUpload('audio-filename', 'audio-blob')) {

    $filepath = $upload->fetch('audio-filename', 'audio-blob');
    $targetFile = $upload->concat($filepath, 'target.pcm');

} else {

    // _process_
    // speech-to-text .. get full transcriptions
    // natural language .. get nouns from each transcript
    //  .. also get articles for each noun?!
    // custom search .. get image for each noun
    // text-to-speech .. get sound for each article+noun
    // return array (result[transcript][noun][article,image,sound] 
    // return array (results[noun][article,image,sound])
    // as json..

    $words = [];

    // if no upload, run recognition
    $targetFile = "uploads/target.pcm";
    $speech = new Speech(PROJECT_ID, LANGUAGE_CODE);
    $fulltranscript = $speech->recognize($targetFile, "LINEAR16", 44100);

    // if something has been found, return it
    if(!empty($fulltranscript)) {
        $transcripts = $speech->getTopResults($fulltranscript);

        // echo "Transcriptions: <br />";
        // echo implode("<br />". PHP_EOL, $transcripts);
        // echo "<br />". PHP_EOL;

        $language = new Language(PROJECT_ID, LANGUAGE_CODE);

        // for each transcript
        foreach ($transcripts as $idx => $transcript) {

            // $language->analyze($transcript);
            $anotation = $language->anotate($transcript);

            if(!empty($anotation)) {

                $nouns = $language->getNouns($anotation);
                foreach ($nouns as $idx2 => $noundata) {
                    $noun = $noundata['text']['content'];
                    $words[$noun] = ['noun' => $noun];
                }

            } else {
                // no nouns found
            }
        }
    } else {
        // no transcript could be found

    }

    // $words['haus'] = ['noun' => 'haus'];

    if(!empty($words)) {
        $googleSearch = new Search("BabyDol Search", "apikey-token.json");

        $optParams = [
            //'imgType' => 'clipart'
            'filter' => 1,
            'safe' => 'high',
            'searchType' => 'image',
            'num' => 5,
            'excludeTerms' => 'stockphoto ytimg',
            'fields' => 'items(image(thumbnailHeight,thumbnailLink,thumbnailWidth),link,mime,pagemap)'
        ];

        foreach ($words as $word => $worddata) {

            // echo "search for word: $word <br />\n";

            $searchResults = $googleSearch->getSearchResults($word, $optParams);
            if(!empty($searchResults)) {
                $searchResult = $searchResults[mt_rand(0, count($searchResults)-1 )];
                $words[$word]['picture'] = $searchResult->link;

                if(!empty($words[$word]['picture'])) {

                
                    $words[$word]['picture'] = $searchResult->image->thumbnailLink;
                    
                    $width = $searchResult->image->thumbnailWidth * 2;
                    $height = $searchResult->image->thumbnailHeight * 2;

                    $word_html = htmlentities($word);
                    $words[$word]['img'] = 
                        "<img alt='$word_html' title='$word_html' src='{$words[$word]['picture']}' width='$width' height='$height' />";
                } else {
                    // echo 'error';
                    $words[$word]['error'] = 'no thumbnail found';
                    $words[$word]['object'] = $searchResults;
                    // var_dump($searchResult);
                }
            } else {
                // no images
            }
        }
    }

    // var_dump($words);
    if(!empty($words)) {
        header('Content-type:application/json;charset=utf-8');
        echo json_encode($words);
    } else {
        http_response_code(500);
        echo 'error';
    }
    

    // $client = new Google_Client();
    // //$client->setAuthConfig('/volume1/web/dev/babydol/BabyDol-cloud-token-1074b826b0e5.json');

    // // $client->addScope(Google_Service_Drive::DRIVE);

    // $service = new Google_Service_Customsearch($client);

    // $optParams = array("cx"=>self::GCSE_SEARCH_ENGINE_ID);    
    // $results = $service->cse->listCse("lol cats", $optParams);

    



}



// $cloud = new ServiceBuilder();



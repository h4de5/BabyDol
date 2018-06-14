<?php

namespace h4de5\BabyDol;

use h4de5\BabyDol\Upload;
use h4de5\BabyDol\Speech;
use h4de5\BabyDol\Language;
use h4de5\BabyDol\Search;


class Controller {

    function __construct () {

    }

    public function handleRequest($action) {
        switch (trim(strtolower($action))) {
            case 'begin':
                $result = $this->beginAction();
            break;
            case 'upload':
                $result = $this->uploadAction();
            break;
            case 'end':
                $result = $this->endAction();
            break;
            default:
                $result = $this->errorAction($message);
            break;
        }
        if($result['status'] == 'error') {
            // header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            //header($_SERVER['SERVER_PROTOCOL'] . ' 400 Error during process', true, 400);
        }
        return $result;
    }

    protected function errorAction($message) {
        
        return [
            'status' => 'error',
            'error' => [$message]
        ];
        // throw \Exception("Unknown action given");
    }

    /**
     * start a new session
     * remove old compound pcm file
     *
     * @return array
     */
    protected function beginAction() {
        $targetFile = "uploads/target.pcm";
        if(file_exists($targetFile)) {
            if(unlink($targetFile)) {
                return ['status' => 'ok'];
            } else {
                return [
                    'status' => 'error', 
                    'error' => ['removal of file failed']
                ];
            }
        } else {
            return [
                'status' => 'error', 
                'error' => ['target file is not available']
            ];
        }
        
    }

    /**
     * upload pcm file
     * append it to target file
     *
     * @return array
     */
    protected function uploadAction() {
        // TODO filename of uploaded file should not be defined in client
        $upload = new Upload('uploads');
        // if files are uploaed (ajax request, add them to target file)
        if($upload->isUpload('audio-filename', 'audio-blob')) {

            $filepath = $upload->fetch('audio-filename', 'audio-blob');
            $targetFile = $upload->concat($filepath, 'target.pcm');

            $result = ['status' => 'ok'];
        } else {
            $result = [
                'status' => 'error', 
                'error' => ['missing upload files']
            ];
        }
    }

    /**
     * start speech recognition 
     * and all follow up analysis of the text
     * _process_
     * speech-to-text .. get full transcriptions
     * natural language .. get nouns from each transcript
     * .. also get articles for each noun
     * custom search .. get image for each noun
     * text-to-speech .. get sound for each article+noun > will be done on client side
     * return array (results[transcript,[{noun,article,image}])
     * as json..
     *
     * @return array
     */
    protected function endAction() {
        $result = [];
        $words = [];
        $fulltranscript = [];

        // if no upload, run recognition
        $targetFile = "uploads/target.pcm";

        if(!file_exists($targetFile)) {
            $result['status'] = "error";
            $result['error'][] = "no target file found";
        } else {

            $speech = new Speech(PROJECT_ID, LANGUAGE_CODE);
            // $transcript = $speech->recognize($targetFile, "LINEAR16", 16000);
            $transcriptObj = $speech->recognize($targetFile, "LINEAR16", 44100);
        }

        //var_dump($fulltranscript);

        // if something has been found, return it
        if(!empty($transcriptObj)) {
            $sentences = $speech->getTopResults($transcriptObj);
            $transcript = implode(" ", $sentences);

            $result['transcript'] = $transcript;

            // echo "Transcriptions: <br />";
            // echo implode("<br />". PHP_EOL, $transcripts);
            // echo "<br />". PHP_EOL;
        } else {
            // no transcript found
            $result['status'] = "error";
            $result['error'][] = "no transcript found";
        }

        if(!empty($transcript)) {
            $language = new Language(PROJECT_ID, LANGUAGE_CODE);
            $anotationObj = $language->anotate($transcript);
            // $language->analyze($transcript);
        }

        if(!empty($anotationObj)) {
            $words = $language->getNouns($anotationObj);
        } else {
            // no annotation result
            $result['status'] = "error";
            $result['error'][] = "no annotation result";
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
            
            if(!empty($words)) {
                $result['status'] = 'ok';
                $result['words'] = array_values($words);
            }
        } else {
            // no nouns found
            $result['status'] = "error";
            $result['error'][] = "no nouns found";
        }

        return $result;
    }
}
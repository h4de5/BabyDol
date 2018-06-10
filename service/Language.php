<?php

namespace h4de5\BabyDol;

use Google\Cloud\Language\LanguageClient;

// text auswertung
// see: https://github.com/GoogleCloudPlatform/google-cloud-php#google-cloud-natural-language-beta
// supported languages:
// see: https://cloud.google.com/natural-language/docs/languages
// API Ref
// see: http://googlecloudplatform.github.io/google-cloud-php/#/docs/google-cloud/v0.58.1/language/languageclient
class Language {

    private $languageClient;

    function __construct ($projectID, $languageCode) {
        // code zu speech client: https://github.com/GoogleCloudPlatform/google-cloud-php-speech/blob/master/src/SpeechClient.php
        $this->languageClient = new LanguageClient([
            'projectId' => $projectID,
            // 'languageCode' => 'en-US'
            'languageCode' => $languageCode
        ]);
    }   

    public function analyze($text) {
        
        echo "analyzing: $text ..<br />";
        // Analyze a sentence.
        $annotation = $this->languageClient->analyzeEntities($text);

        foreach ($annotation->entities() as $entity) {
            echo $entity['type'];
        }

    }

    public function anotate($text) {
        $options = [
            'features' => [
                'syntax', 
                'entities', 
                // 'sentiment' .. not available in de_DE
            ]
        ];
        // Analyze a sentence.
        $anotation = $this->languageClient->annotateText($text, $options);

        // Check the sentiment.
        // not available in de_de
        // if ($anotation->sentiment() > 0) {
        //     echo "This is a positive message.<br />".PHP_EOL;
        // }

        // Parse the syntax.
        // get nouns from text
        

        /*
        foreach ($tokens as $token) {
            echo $token['text']['content'] . "<br />".PHP_EOL;
        }
        */
        return $anotation;
    }

    public function getNouns($anotation) {
        $tokens = $anotation->tokensByTag('NOUN');
        return $tokens; 
    }


}
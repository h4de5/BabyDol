<?php

namespace h4de5\BabyDol;

use Google\Cloud\Speech\SpeechClient;
// use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\StreamingRecognitionConfig;
use Google\Cloud\Speech\V1\StreamingRecognizeRequest;
use Google\Cloud\Speech\V1\RecognitionConfig_AudioEncoding;

// sprach erkennung
// see: https://github.com/GoogleCloudPlatform/google-cloud-php#google-cloud-speech-alpha
// spracherkennung von stream
// funktioniert nur über gRPC (nodejs server)
// see: https://cloud.google.com/speech-to-text/docs/streaming-recognize

class Speech {

    private $speechClient;

    function __construct ($projectID, $languageCode) {
        // code zu speech client: https://github.com/GoogleCloudPlatform/google-cloud-php-speech/blob/master/src/SpeechClient.php
        $this->speechClient = new SpeechClient([
            'projectId' => $projectID,
            // 'languageCode' => 'en-US'
            'languageCode' => $languageCode
        ]);
    }

    /**
     * returns top alternatives from results
     *
     * @param object[] $results from speechClient->recognize
     * @return void
     */
    public function getTopResults($results) {
        $transcription = [];

        if(!empty($results)) {
            foreach ($results as $result) {
                foreach ($result->alternatives() as $idx => $alternative) {
                    if($idx > 2) continue;
                    $transcription[] = $alternative['transcript'];
                }
                // echo $result->topAlternative()['transcript'] . "\n";
                // echo 'Transcription: ' . $result->topAlternative()['transcript'] . PHP_EOL;
                // echo 'Transcription: ' . $result->alternatives()[0]['transcript'] . PHP_EOL;
                // $transcription[] = $result->topAlternative()['transcript'];
            }
        }

        return $transcription;
    }

    /**
     * recognize audiofile from filesystem
     *
     * @param string $audioFile path to audio file
     * @param string $encoding
     * @param integer $sampleRateHertz
     * @return object[]
     */
    public function recognize($audioFile, $encoding = "LINEAR16", $sampleRateHertz = 44100) {
      
        // The audio file's encoding and sample rate
        $options = [
            'encoding' => $encoding,
            'sampleRateHertz' => $sampleRateHertz,
            'profanityFilter' => true,
        ];

        // Recognize the speech in an audio file.
        $results = $this->speechClient->recognize(
            fopen($audioFile, 'r'), 
            $options
        );

        return $results;
    }



        
    /**
     * Transcribe an audio file using Google Cloud Speech API
     * Example:
     * ```
     * $audoEncoding =  Google\Cloud\Speech\V1\RecognitionConfig_AudioEncoding::WAV
     * streaming_recognize('/path/to/audiofile.wav', 'en-US');
     * ```.
     *
     * @param string $audioFile path to an audio file.
     * @param string $languageCode The language of the content to
     *     be recognized. Accepts BCP-47 (e.g., `"en-US"`, `"es-ES"`).
     * @param string $encoding
     * @param string $sampleRateHertz
     *
     * @return string the text transcription
     */
    public function streamingRecognize($audioFile, $encoding, $sampleRateHertz)
    {
        if (!defined('Grpc\STATUS_OK')) {
            throw new \Exception('Install the grpc extension ' .
                '(pecl install grpc)');
        }

        $speechClient = new SpeechClient();
        try {
            $config = new RecognitionConfig();
            $config->setLanguageCode(LANGUAGE_CODE);
            $config->setSampleRateHertz($sampleRateHertz);
            // encoding must be an enum, convert from string
            $encodingEnum = constant(RecognitionConfig_AudioEncoding::class . '::' . $encoding);
            $config->setEncoding($encodingEnum);

            $strmConfig = new StreamingRecognitionConfig();
            $strmConfig->setConfig($config);

            $strmReq = new StreamingRecognizeRequest();
            $strmReq->setStreamingConfig($strmConfig);

            $strm = $speechClient->streamingRecognize();
            $strm->write($strmReq);

            $strmReq = new StreamingRecognizeRequest();
            $f = fopen($audioFile, "rb");
            $fsize = filesize($audioFile);
            $bytes = fread($f, $fsize);
            $strmReq->setAudioContent($bytes);
            $strm->write($strmReq);

            foreach ($strm->closeWriteAndReadAll() as $response) {
                foreach ($response->getResults() as $result) {
                    foreach ($result->getAlternatives() as $alt) {
                        printf("Transcription: %s\n", $alt->getTranscript());
                    }
                }
            }
        } finally {
            $speechClient->close();
        }
    }

}
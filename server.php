<?php

header("Access-Control-Allow-Origin: *");

require 'vendor/autoload.php';

define("PROJECT_ID", 'babydol-upheld-archway-205100');
define("LANGUAGE_CODE", 'de-DE');

// $projectId = 'babydol-upheld-archway-205100';
// $languageCode = 'de-DE';

use Google\Cloud\Core\ServiceBuilder;
// API KEY: 
// AIzaSyBAJFhsgtwCaduXytmC1pI98Y6jZ5rbzHQ
putenv('GOOGLE_APPLICATION_CREDENTIALS=/volume1/web/dev/babydol/BabyDol-cloud-token-1074b826b0e5.json');


function selfInvoker()
{
	if (!isset($_POST['audio-filename']) && !isset($_POST['video-filename'])) {
		echo 'PermissionDeniedError';
		return;
	}

	$fileName = '';
	$tempName = '';

	if(isset($_POST['audio-filename'])) {
		$fileName = $_POST['audio-filename'];
		$tempName = $_FILES['audio-blob']['tmp_name'];
	} else {
		$fileName = $_POST['video-filename'];
		$tempName = $_FILES['video-blob']['tmp_name'];
	}

	if (empty($fileName) || empty($tempName)) {
		echo 'PermissionDeniedError';
		return;
	}
	$filePath = 'uploads/' . $fileName;

	// make sure that one can upload only allowed audio/video files
	$allowed = array(
		'webm',
		'wav',
		'mp4',
		'mp3',
		'ogg',
		'pcm'
	);
	$extension = pathinfo($filePath, PATHINFO_EXTENSION);
	if (!$extension || empty($extension) || !in_array($extension, $allowed)) {
		echo 'PermissionDeniedError';
		// continue;
		return;
	}

	if (!move_uploaded_file($tempName, $filePath)) {
		echo ('Problem saving file.');
		return;
	}

	

	echo ($filePath);

	return $filePath;
}

function concatFiles($newFile) {
	$targetFile = "uploads/target.pcm";

	$handle = fopen($newFile, "r");
	$contents = fread($handle, filesize($newFile));
	fclose($handle);

	// Sichergehen, dass die Datei existiert und beschreibbar ist.

	if (!$handle = fopen($targetFile, "a")) {
			print "Kann die Datei $targetFile nicht öffnen";
			exit;
	}

	// Schreibe $somecontent in die geöffnete Datei.
	if (!fwrite($handle, $contents)) {
			print "Kann in die Datei $targetFile nicht schreiben";
			exit;
	}

	fclose($handle);

	return $targetFile;
}

$filepath = selfInvoker();
if(!empty($filepath)) {
	$targetFile = concatFiles($filepath);
}

$targetFile = "uploads/target.pcm";

use Google\Cloud\Speech\SpeechClient;

// $cloud = new ServiceBuilder();

function SpeechClient($audioFile, $encoding, $sampleRateHertz) {
	// sprach erkennung
	// see: https://github.com/GoogleCloudPlatform/google-cloud-php#google-cloud-speech-alpha
	// spracherkennung von stream
	// see: https://cloud.google.com/speech-to-text/docs/streaming-recognize

	// code zu speech client: https://github.com/GoogleCloudPlatform/google-cloud-php-speech/blob/master/src/SpeechClient.php
	$speech = new SpeechClient([
		'projectId' => PROJECT_ID,
		// 'languageCode' => 'en-US'
		'languageCode' => LANGUAGE_CODE
	]);

	# The audio file's encoding and sample rate
	$options = [
		'encoding' => $encoding,
		'sampleRateHertz' => $sampleRateHertz,
	];

	// Recognize the speech in an audio file.
	$results = $speech->recognize(
		// fopen(__DIR__ . '/audio_sample.flac', 'r')
		fopen($audioFile, 'r'), 
		$options
	);

	foreach ($results as $result) {
		// echo $result->topAlternative()['transcript'] . "\n";
		echo 'Transcription: ' . $result->topAlternative()['transcript'] . PHP_EOL;
		// echo 'Transcription: ' . $result->alternatives()[0]['transcript'] . PHP_EOL;
	}
}

// use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\StreamingRecognitionConfig;
use Google\Cloud\Speech\V1\StreamingRecognizeRequest;
use Google\Cloud\Speech\V1\RecognitionConfig_AudioEncoding;

	
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
function SpeechClientStream($audioFile, $encoding, $sampleRateHertz)
{
    // if (!defined('Grpc\STATUS_OK')) {
    //     throw new \Exception('Install the grpc extension ' .
    //         '(pecl install grpc)');
    // }

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


if(!empty($targetFile)) {
	// SpeechClientStream($targetFile, "LINEAR16", 44100);
	SpeechClient($targetFile, "LINEAR16", 44100);
}

use Google\Cloud\Language\LanguageClient;

function LanguageClient() {
	
	// text auswertung
	// see: https://github.com/GoogleCloudPlatform/google-cloud-php#google-cloud-natural-language-beta

	$language = new LanguageClient([
			'projectId' => PROJECT_ID,
			'languageCode' => LANGUAGE_CODE
	]);

	// Analyze a sentence.
	$annotation = $language->annotateText('Hallo Du!');

	// Check the sentiment.
	if ($annotation->sentiment() > 0) {
			echo "This is a positive message.\n";
	}

	// Parse the syntax.
	$tokens = $annotation->tokensByTag('NOUN');

	foreach ($tokens as $token) {
			echo $token['text']['content'] . "\n";
	}
}
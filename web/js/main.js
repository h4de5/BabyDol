// what does not work / will no work:
// there is no way to use the streaming speech recognition api from google over php api
// it would need a grpc setup (nodejs) on the server side
// see: https://cloud.google.com/speech-to-text/docs/streaming-recognize#speech-streaming-recognize-php
// https://grpc.io/docs/quickstart/php.html
// websocket connection maybe?

$( document ).ready(function() {
	// input audio stream
	// var streams;
	var mediaRecorder;

  var $record = $('#record');
  var $analyse = $('#analyse');

	// see explanation: https://stackoverflow.com/a/15104053/2115610
	// NOT IN USE
	//var context = new AudioContext();
	// var source = context.createMediaStreamSource(stream);
	//var source;
	// var source = context.createBufferSource();
  //var processor = context.createScriptProcessor(16384, 1, 1);

	////// WEBRTC MediaRecorder
	// see: https://github.com/streamproc/MediaStreamRecorder
	// initializes mediaDevices for recorder
	var captureUserMedia = function (mediaConstraints, successCallback, errorCallback) {
		navigator.mediaDevices.getUserMedia(mediaConstraints).then(successCallback).catch(errorCallback);
	};

	var onMediaSuccess = function(stream) {

		/*
		var audio = document.createElement('audio');
		audio = mergeProps(audio, {
			controls: true,
			muted: true
		});
		audio.srcObject = stream;
		audio.play();
		*/

		// audiosContainer.appendChild(audio);
		// audiosContainer.appendChild(document.createElement('hr'));
		mediaRecorder = new MediaStreamRecorder(stream);
		// mediaRecorder.stream = stream;

		mediaRecorder.recorderType = StereoAudioRecorder;
		mediaRecorder.mimeType = 'audio/pcm';

		// var recorderType = document.getElementById('audio-recorderType').value;
		// if (recorderType === 'MediaRecorder API') {
		// 	mediaRecorder.recorderType = MediaRecorderWrapper;
		// }
		// if (recorderType === 'WebAudio API (WAV)') {
		// 	mediaRecorder.recorderType = StereoAudioRecorder;
		// 	mediaRecorder.mimeType = 'audio/wav';
		// }
		// if (recorderType === 'WebAudio API (PCM)') {
		// 	mediaRecorder.recorderType = StereoAudioRecorder;
		// 	mediaRecorder.mimeType = 'audio/pcm';
		// }
		// don't force any mimeType; use above "recorderType" instead.
		// mediaRecorder.mimeType = 'audio/webm'; // audio/ogg or audio/wav or audio/webm
		mediaRecorder.audioChannels = 1;
		//mediaRecorder.bufferSize = 1024 * 16;
		mediaRecorder.sampleRate = 44100;
		//mediaRecorder.sampleRate = 16000;


		mediaRecorder.ondataavailable = function(blob) {
			sendStream(blob);
		};
		mediaRecorder.onstop = function() {
			// recording has been stopped.
			console.log('recording has been stopped.');
		};

		var formData = new FormData();
		formData.append('action', 'begin');
		makeHttpRequest(formData, evaluateResult);

		// get blob after specific time interval ms
		mediaRecorder.start(1000);

		$record.val("recording..");
    $record.one('click', stopRecord);
    $analyse.prop('disabled', true);

	};

	var onMediaError = function(err) {
		stopRecord(false);
    console.log("Stream access failed: ", err.message);

    $container = $("#error-container");
    showError($container, "onMediaError", "Stream access failed: " + err.message);
  }

  // asks user for permissions
	var getPermission = function() {
    clearError();

		console.log('getPermission..');
		$record.val("waiting for permission..");

		// prepareMediaDevices();

		if(navigator.mediaDevices) {
			console.log('supported constraints:', navigator.mediaDevices.getSupportedConstraints());
			captureUserMedia({audio: true}, onMediaSuccess, onMediaError);

			/*
			navigator.mediaDevices.getUserMedia(
				{audio: true, video: false}
			)
			.then(startRecord)
			.catch(function(err) {
				stopRecord();
				console.log("Stream access failed: ", err.message);
			});
			*/
		} else {
			stopRecord(false);
      console.log("Stream access failed: no mediaDevices found - update chrome?");
      $container = $("#error-container");
      showError($container, "getPermission", "Stream access failed: no mediaDevices found - update chrome?");
		}
  };


	var sendStream = function(blob) {

		var file = new File([blob], 'msr-' + (new Date).toISOString().replace(/:|\./g, '-') + '.pcm', {
			type: 'audio/pcm'
		});

		// create FormData
		var formData = new FormData();
		formData.append('action', 'upload');
		formData.append('audio-filename', file.name);
		formData.append('audio-blob', blob);

		makeHttpRequest(formData, evaluateResult);
	};

	var stopRecord = function(analysis = true) {
		console.log('stopRecord..');

		// TODO - send concat command to server
		mediaRecorder.stop();
    $record.one('click', getPermission);
    $record.val("start record");

    if(analysis) {
      console.log("analyse record..")
      $analyse.trigger( "click" );
    }
		// var formData = new FormData();
		// formData.append('action', 'end');
		// makeHttpRequest(formData, evaluateResult );
	};

	var speak = function( text ) {
		var msg = new SpeechSynthesisUtterance(text);

		if ('speechSynthesis' in window) {
		// Synthesis support. Make your web apps talk!
			window.speechSynthesis.speak(msg);
		} else {
      console.log("Text-to-speech is not supported in your browser :(");
      $container = $("#error-container");
      showError($container, "getPermission", "Text-to-speech is not supported in your browser :(");
		}

		if ('SpeechRecognition' in window) {
			// Speech recognition support. Talk to your apps!
		}
  }

  var showError = function($container, element, html) {
    $error = $("#error-stub").clone().prop({ id: "id-"+element.replace(/ /g,"_"), class: "error-element"});
    $error.children("div").text(element);
    $error.children("span").html(html);
    $error.show();
    $error.appendTo($container);
  }
  var clearError = function() {
    $container = $("#error-container");
    $container.children( ".error-element" ).remove();
  }

	var evaluateResult = function(data) {

    clearError();

    if(typeof data === 'string') {
      $container = $("#error-container");
      showError($container, "raw Output", data);
    }
    if(data.error) {
      $container = $("#error-container");

      data.error.forEach(element => {
        showError($container, element, '');
      });

      if(typeof data.raw_output != 'undefined') {
        showError($container, "raw Output", data.raw_output);
      }
    }

    if(data.transcript) {

      $container = $("#transcript-container");
      $container.children( ".transcript-element" ).remove();

      $transcript = $("#transcript-stub").clone().prop({ id: "id-transcript", class: "transcript-element"});
      $transcript.children("div").text(data.transcript);
      $transcript.on('click', function() { speak(data.transcript); });

      $transcript.show();
      $transcript.appendTo($container);

    }

		if(data.words) {
			// console.log('found words: ', data.words);
			$container = $("#result-container");

			// remove all existing results first
			$container.children( ".result-element" ).remove();

			data.words.forEach(element => {

				var name = element.article +" "+ element.word
				console.log('working on element: ', element);

				$word = $("#result-stub").clone().prop({ id: "id-"+element.word, name: name, class: "result-element"});
				$word.children("img").attr({src: element.picture, title: name, alt: name});
				$word.children("span").text(name);

				$word.on('click', function() { speak(name); });

				$word.show();
				$word.appendTo($container);
			});
		}
	}

  // sends formdata with chunks to server
  // will be also triggered at the start and the end of a record
	var makeHttpRequest = function(formData, callback) {
		var jqxhr = $.ajax({
			url: 'server.php',
			data: formData,
			processData: false,
			contentType: false,
      type: 'POST'
      })
			.done(function(data){
				console.log("success: ", data);
				callback(data);
		  })
			.fail(function(data) {
        console.log( "error", data );
        callback(data.responseJSON);
      })
      .always(function() {
        if(formData.has('action') && formData.get('action') == 'end') {
          // console.log( "this should only come at the end", formData, formData.has('action'));
          $record.prop('disabled', false);
          $analyse.prop('disabled', false);
          $analyse.val("redo analysis");
        }
			});
  }

  // add event to button
	$record.one('click', getPermission);

	$analyse.on('click', function(event) {
		var formData = new FormData();
    formData.append('action', 'end');
    makeHttpRequest(formData, evaluateResult );
    $analyse.val("analysing..");
    $analyse.prop('disabled', true);
    $record.prop('disabled', true);
	});

});
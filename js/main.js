// what does not work / will no work:
// there is no way to use the streaming speech recognition api from google over php api
// it would need a grpc setup (nodejs) on the server side
// see: https://cloud.google.com/speech-to-text/docs/streaming-recognize#speech-streaming-recognize-php
// https://grpc.io/docs/quickstart/php.html
// websocket connection maybe?

$( document ).ready(function() {
	// input audio stream
	var streams;
	var mediaRecorder;

	// get player
	// var player = document.getElementById('player');
	var player = $('#player').get(0);
	var $record = $('#record');
	var $speak = $('#speak');

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
			//stopRecord();
		};

		// get blob after specific time interval ms
		mediaRecorder.start(1000);

		$record.val("recording..");
		$record.one('click', stopRecord);

	};

	var onMediaError = function(err) {
		stopRecord();
		console.log("Stream access failed: ", err.message);
	}

	var sendStream = function(blob) {
		
		var file = new File([blob], 'msr-' + (new Date).toISOString().replace(/:|\./g, '-') + '.pcm', {
			type: 'audio/pcm'
		});
	
		// create FormData
		var formData = new FormData();
		formData.append('audio-filename', file.name);
		formData.append('audio-blob', blob);
	
		makeHttpRequest(formData);
	};

	var stopRecord = function() {
		console.log('stopRecord..');

		// TODO - send concat command to server
		mediaRecorder.stop();
		$record.val("start record");
		$record.one('click', getPermission);
	};



	// if user allowed access
	// getting stream from browser
	// NOT IN USE
	var startRecord = function(stream) {
		console.log('startRecord..');

		// forward audio stream to player
		// try {
		// 	player.srcObject = stream;
		// } catch (error) {
		// 	if (window.URL) {
		// 		player.src = window.URL.createObjectURL(stream);
		// 	} else {
		// 		player.src = stream;
		// 	}
		// }

		
		// echo playback
		if (window.URL) {
			player.src = window.URL.createObjectURL(stream);
		} else {
			player.src = stream;
		}

		source = context.createMediaStreamSource(stream);
	
		source.connect(processor);
		processor.connect(context.destination);
		// source.start();

		processor.onaudioprocess = function(audioProcessingEvent) {
		  // Do something with the data, i.e Convert this to WAV
			// console.log(audioProcessingEvent.inputBuffer);
			
			// see: https://webrtcexperiment-webrtc.netdna-ssl.com/MediaStreamRecorder.js
			sendStream(convertBufferToStream(audioProcessingEvent.inputBuffer));

			

		  // // see: https://developer.mozilla.org/en-US/docs/Web/API/BaseAudioContext/createScriptProcessor
		  // // The input buffer is the song we loaded earlier
			// var inputBuffer = audioProcessingEvent.inputBuffer;

			// // The output buffer contains the samples that will be modified and played
			// var outputBuffer = audioProcessingEvent.outputBuffer;

			// // Loop through the output channels (in this case there is only one)
			// for (var channel = 0; channel < outputBuffer.numberOfChannels; channel++) {
			// 	var inputData = inputBuffer.getChannelData(channel);
			// 	var outputData = outputBuffer.getChannelData(channel);

			// 	// Loop through the 4096 samples
			// 	for (var sample = 0; sample < inputBuffer.length; sample++) {
			// 	// make output equal to the same as the input
			// 	outputData[sample] = inputData[sample];

			// 	// add noise to each output sample
			// 	outputData[sample] += ((Math.random() * 2) - 1) * 0.2;         
			// 	}
			// }
		};

		// When the buffer source stops playing, disconnect everything
		source.onended = function() {
			console.log("source has ended..");
			// source.disconnect(processor);
			// processor.disconnect(context.destination);

			stopRecord();
		}
				
		$record.val("recording..");
		$record.one('click', stopRecord);

		// save stream to stop it later
		streams = stream;
	};

	// asks user for permissions
	var getPermission = function() {
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
			stopRecord();
			console.log("Stream access failed: no mediaDevices found - update chrome?");
		}
	};

	// stop record
	// NOT IN USe
	var stopRecord_notinuse = function() {
		console.log('stopRecord..');

		player.pause();
		player.src = player.src;

		if(streams) {
			streams.getTracks().forEach(track => track.stop())
		}

		source.disconnect(processor);
		processor.disconnect(context.destination);

		$record.val("start record");
		$record.one('click', getPermission);
	};

	var speak = function(text ) {
		var msg = new SpeechSynthesisUtterance(text);

		if ('speechSynthesis' in window) {
		// Synthesis support. Make your web apps talk!
			window.speechSynthesis.speak(msg);
		} else {
			console.log("Text-to-speech is not supported in your browser :(");
		}
		
		if ('SpeechRecognition' in window) {
			// Speech recognition support. Talk to your apps!
		}
	}

	// NOT IN USE
	function convertoFloat32ToInt16(buffer) {
		var l = buffer.length;
		var buf = new Int16Array(l)

		while (l--) {
			buf[l] = buffer[l] * 0xFFFF; //convert to 16 bit
		}
		return buf.buffer
	}

	// NOT IN USE
	var convertBufferToStream = function(buffer) {
		var interleaved = new Float32Array(buffer.getChannelData(0));

		/*
		var blob = new Blob([convertoFloat32ToInt16(interleaved)], {
			type: 'audio/pcm'
		});
		*/
		var chunks = [convertoFloat32ToInt16(interleaved)];

		return chunks;
	}

	// see: https://github.com/streamproc/MediaStreamRecorder#upload-to-php-server
	// NOT IN USE
	var sendStream_notinuse = function(chunks) {
		// blob = new Blob(chunks, {
		// 	type: 'audio/wav'
		// });
		blob = new Blob(chunks, {
		 	type: 'audio/pcm'
		});
		var fileType = 'audio'; // or "audio"
		var fileName = 'save.pcm'; // or "wav"

		var formData = new FormData();
		formData.append(fileType + '-filename', fileName);
		formData.append(fileType + '-blob', blob);

		makeHttpRequest(formData);

	};

	// sends formdata with chunks to server
	var makeHttpRequest = function(formData) {
		var jqxhr = $.ajax({
			url: 'server.php',
			data: formData,
			processData: false,
			contentType: false,
			type: 'POST',
			success: function(data){
				console.log("success: ", data);
			}
		  })
		  	.done(function() {
				console.log( "second success" );
			})
			.fail(function() {
				console.log( "error" );
			});
	}

	// send stop command to server, gets json
	var evaluateStream = function() {
		xhr('server.php', formData, function (fileURL) {
			window.open(fileURL);
		});
	}

	// add event to button
	$record.one('click', getPermission);

	$speak.on('click', function(event) {
		speak('Hallo Du, wie geht es dir? haus pferd');
	});

	// record.trigger('click');

});
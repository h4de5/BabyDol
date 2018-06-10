// what does not work / will no work:
// there is no way to use the streaming speech recognition api from google over php api
// it would need a grpc setup (nodejs) on the server side
// see: https://cloud.google.com/speech-to-text/docs/streaming-recognize#speech-streaming-recognize-php
// https://grpc.io/docs/quickstart/php.html
// websocket connection maybe?

$( document ).ready(function() {
	// input audio stream
	var streams;

	// get player
	// var player = document.getElementById('player');
	var player = $('#player').get(0);
	var $record = $('#record');

	// see explanation: https://stackoverflow.com/a/15104053/2115610
	var context = new AudioContext();
	// var source = context.createMediaStreamSource(stream);
	var source;
	// var source = context.createBufferSource();
	var processor = context.createScriptProcessor(16384, 1, 1);

	// polyfill - some browsers does not support mediaDevices
	var prepareMediaDevices = function() {
		if (navigator.mediaDevices === undefined) {
			navigator.mediaDevices = {};
		}

		// Some browsers partially implement mediaDevices. We can't just assign an object
		// with getUserMedia as it would overwrite existing properties.
		// Here, we will just add the getUserMedia property if it's missing.
		if (navigator.mediaDevices.getUserMedia === undefined) {
			
			navigator.mediaDevices.getUserMedia = function(constraints) {
		
				// First get ahold of the legacy getUserMedia, if present
				var getUserMedia = navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
		
				// Some browsers just don't implement it - return a rejected promise with an error
				// to keep a consistent interface
				if (!getUserMedia) {
					return Promise.reject(new Error('getUserMedia is not implemented in this browser'));
				}
		
				// Otherwise, wrap the call to the old navigator.getUserMedia with a Promise
				return new Promise(function(resolve, reject) {
					getUserMedia.call(navigator, constraints, resolve, reject);
				});
			}
		}
	};

	// if user allowed access
	// getting stream from browser
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

			navigator.mediaDevices.getUserMedia(
				{audio: true, video: false}
			)
			.then(startRecord)
			.catch(function(err) {
				stopRecord();
				console.log("Stream access failed: ", err.message);
			});
		} else {
			stopRecord();
			console.log("Stream access failed: no mediaDevices found - update chrome?");
		}
	};

	// stop record
	var stopRecord = function() {
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

	function convertoFloat32ToInt16(buffer) {
		var l = buffer.length;
		var buf = new Int16Array(l)

		while (l--) {
				buf[l] = buffer[l] * 0xFFFF; //convert to 16 bit
		}
		return buf.buffer
	}

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
	var sendStream = function(chunks) {
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

	};

	// send stop command to server, gets json
	var evaluateStream = function() {
		xhr('server.php', formData, function (fileURL) {
			window.open(fileURL);
		});
	}

	// add event to button
	$record.one('click', getPermission);

	// record.trigger('click');

});
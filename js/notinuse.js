
	// polyfill - some browsers does not support mediaDevices
	// not in use
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
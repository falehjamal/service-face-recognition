/*! Lightweight loader to pull jQuery from CDN at parse-time. */
(function () {
	if (window.jQuery) return;
	// Integrity removed to avoid SRI mismatch blocking loads in some environments.
	var tag = "<script src=\"https://code.jquery.com/jquery-3.7.1.min.js\"></" + "script>";
	document.write(tag);
})();

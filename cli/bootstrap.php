<?php


/**
 * Retrieve path (taken from cli_dispatch.phpsh)
 */

	// Get path to this script
$temp_PATH_thisScript = isset($_SERVER['argv'][0]) ? $_SERVER['argv'][0] : (isset($_ENV['_']) ? $_ENV['_'] : $_SERVER['_']);

	// Figure out if the path is relative
$relativePath = FALSE;
if (stristr(PHP_OS,'win') && !stristr(PHP_OS,'darwin')) {
		// Windows
	if (!preg_match('/^([A-Z]:)?\\\/', $temp_PATH_thisScript)) {
		$relativePath = TRUE;
	}
} else {
		// *nix, et al
	if ($temp_PATH_thisScript{0} != '/') {
		$relativePath = TRUE;
	}
}

	// Resolve path
if ($relativePath) {
	$workingDirectory = $_SERVER['PWD'] ? $_SERVER['PWD'] : getcwd();
	if ($workingDirectory) {
		$temp_PATH_thisScript =
			$workingDirectory.'/'.ereg_replace('\.\/','',$temp_PATH_thisScript);
		if (!@is_file($temp_PATH_thisScript)) {
			die ('Relative path found, but an error occured during resolving of the absolute path: '.$temp_PATH_thisScript.chr(10));
		}
	} else {
		die ('Relative path found, but resolving absolute path is not supported on this platform.'.chr(10));
	}
}

$documentRoot = str_replace('typo3conf/ext/crawler/cli/bootstrap.php', '', $temp_PATH_thisScript);


/**
 * Second paramater is a base64 encoded serialzed array of header data
 */
$additionalHeaders = unserialize(base64_decode($_SERVER['argv'][2]));
foreach ($additionalHeaders as $additionalHeader) {
	list($key, $value) = explode(':', $additionalHeader, 2);
	$key = str_replace('-', '_', strtoupper(trim($key)));
	if ($key != 'HOST') {
		$_SERVER['HTTP_'.$key] = $value;
	}
}


// put parsed query parts into $_GET array
$urlParts = parse_url($_SERVER['argv'][1]);
parse_str($urlParts['query'], $_GET);

// faking the environment
$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = $urlParts['host'];
$_SERVER['HTTP_USER_AGENT'] = 'CLI Mode';
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $urlParts['path'];
$_SERVER['SCRIPT_FILENAME'] = $documentRoot . $_SERVER['SCRIPT_NAME'];
$_SERVER['QUERY_STRING'] = $urlParts['query'];
$_SERVER['DOCUMENT_ROOT'] = '';
$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . (empty($_SERVER['QUERY_STRING']) ? '' : '?'.$_SERVER['QUERY_STRING']);

include($documentRoot.'index.php');

?>
<?php


/**
 * Retrieve path (taken from cli_dispatch.phpsh)
 */
// echo realpath(dirname(__FILE__).'/../../../..'), "\n";

	// Get path to this script
$temp_PATH_thisScript = isset($_SERVER['argv'][0]) ? $_SERVER['argv'][0] : (isset($_ENV['_']) ? $_ENV['_'] : $_SERVER['_']);

	// Resolve path
if (!isAbsPath($temp_PATH_thisScript)) {
	$workingDirectory = $_SERVER['PWD'] ? $_SERVER['PWD'] : getcwd();
	if ($workingDirectory) {
		$temp_PATH_thisScript =
			$workingDirectory.'/'.preg_replace('/\.\//','',$temp_PATH_thisScript);
		if (!@is_file($temp_PATH_thisScript)) {
			die ('Relative path found, but an error occured during resolving of the absolute path: '.$temp_PATH_thisScript.chr(10));
		}
	} else {
		die ('Relative path found, but resolving absolute path is not supported on this platform.'.chr(10));
	}
}

$typo3Root = preg_replace('#typo3conf/ext/crawler/cli/bootstrap.php$#', '', $temp_PATH_thisScript);



/**
 * Second paramater is a base64 encoded serialzed array of header data
 */
$additionalHeaders = unserialize(base64_decode($_SERVER['argv'][3]));
if (is_array($additionalHeaders)) {
	foreach ($additionalHeaders as $additionalHeader) {
		if (strpos($additionalHeader, ':') !== false) {
			list($key, $value) = explode(':', $additionalHeader, 2);
			$key = str_replace('-', '_', strtoupper(trim($key)));
			if ($key != 'HOST') {
				$_SERVER['HTTP_'.$key] = $value;
			}
		}
	}
}


// put parsed query parts into $_GET array
$urlParts = parse_url($_SERVER['argv'][2]);
// Populating $_GET
parse_str($urlParts['query'], $_GET);
// Populating $_REQUEST
parse_str($urlParts['query'], $_REQUEST);
// Populating $_POST
$_POST = array();
// Populating $_COOKIE
$_COOKIE = array();

// Get the TYPO3_SITE_PATH of the website frontend:
$typo3SitePath = $_SERVER['argv'][1];

// faking the environment
$_SERVER['ORIG_SCRIPT_FILENAME'] = '';
$_SERVER['DOCUMENT_ROOT'] = preg_replace('#' . preg_quote($typo3SitePath, '#') . '$#', '', $typo3Root);
$_SERVER['HTTP_USER_AGENT'] = 'CLI Mode';
$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = $urlParts['host'];
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $typo3SitePath . 'index.php';
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'] = $typo3Root . 'index.php';
$_SERVER['QUERY_STRING'] = (isset($urlParts['query']) ? $urlParts['query'] : '');
$_SERVER['REQUEST_URI'] = $urlParts['path'] . (isset($urlParts['query']) ? '?' . $urlParts['query'] : '');
$_SERVER['REQUEST_METHOD'] = 'GET';

// Define a port if used in the URL: 
if (isset($urlParts['port'])) {
	$_SERVER['HTTP_HOST'] .= ':' . $urlParts['port'];
	$_SERVER['SERVER_PORT'] = $urlParts['port'];
}
// Define HTTPS disposal:
if ($urlParts['scheme'] === 'https') {
	$_SERVER['HTTPS'] = 'on';
}

chdir($typo3Root);
include($typo3Root . '/index.php');


/**
 * Checks if the $path is absolute or relative (detecting either '/' or 'x:/' as first part of string) and returns TRUE if so.
 *
 * @param string $path File path to evaluate
 * @return boolean
 */
function isAbsPath($path) {
	// on Windows also a path starting with a drive letter is absolute: X:/
	if (stristr(PHP_OS,'win') && substr($path, 1, 2) === ':/') {
		return TRUE;
	}

	// path starting with a / is always absolute, on every system
	return (substr($path, 0, 1) === '/');
}
?>
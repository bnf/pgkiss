<?php
// nice error reporting doggie
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// settings
define('REPODIR', getcwd(). '/repos');

// access check
//
// source code commit @ Oct 21, 2012:
// https://github.com/git/git/blob/8c7a786b6c8eae8eac91083cdc9a6e337bc133b0/http-backend.c
// " {"POST", "/git-upload-pack$", service_rpc},
//   {"POST", "/git-receive-pack$", service_rpc}
// 
// and implicated (read to be "/git-upload-pack") in http://www.kernel.org/pub/software/scm/git/docs/git-http-backend.html
// " To enable anonymous read access but authenticated write access, require authorization with a LocationMatch directive:
//   <LocationMatch "^/git/.*/git-receive-pack$">
//
/* TODO:
- implement forceidentity.. with hooks :(
- http.getanyfile false
- http.uploadpack true // true by default, though force it just in case // no fuck's sake. this is what http-backend is for!?
- http.receivepack true
- GIT_COMMITTER_NAME, GIT_COMMITTER_EMAIL
  as of 10.11.2012 regarding envvar overwriting: https://github.com/git/git/commit/e32a4581bcbf1cf43cd5069a0d19df07542d612a
 */

// set environment for the program
$pEnv = array(
	'GIT_PROJECT_ROOT' => '.'
	);
setIf($pEnv, 'PATH_INFO', $_SERVER['PATH_INFO']);
setIf($pEnv, 'REMOTE_USER', $_SERVER['REMOTE_USER']);
setIf($pEnv, 'REMOTE_ADDR', $_SERVER['REMOTE_ADDR']);
setIf($pEnv, 'CONTENT_TYPE', $_SERVER['CONTENT_TYPE']);
setIf($pEnv, 'QUERY_STRING', $_SERVER['QUERY_STRING']);
setIf($pEnv, 'REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);

// in addition, Accept and Content-Type were observed being used by git http-backend
setIf($pEnv, 'HTTP_ACCEPT', $_SERVER['HTTP_ACCEPT']);
setIf($pEnv, 'CONTENT_LENGTH', $_SERVER['CONTENT_LENGTH']);

// start program
$pHandle = proc_open(
//	'bash --norc --noprofile -c \'git http-backend 2>&1 | tee -a pgkiss.log\'',
	'git http-backend',
	array(
		0 => array('pipe', 'r'),
		1 => array('pipe', 'w'),
		2 => array('file', 'err.log', 'a'),
	),
	$pPipes,
	REPODIR,
	$pEnv,
	array('bypass_shell' => true)
	);
if ($pHandle === false) err('Failed to execute process');

// friendly names for handles to/from program
$pReadHandle = $pPipes[1];
$pWriteHandle = $pPipes[0];


// open browser's input
$cReadHandle = fopen('php://input', 'rb');
if ($cReadHandle === false) {
	err('Failed to open client\'s stream-to-read');
}

// ..and output streams
$cWriteHandle = fopen('php://output', 'rb');
if ($cWriteHandle === false) {
	err('Failed to open client\'s stream-to-write');
}

// set streams to non-blocking mode
$succ = true;
$succ &= stream_set_blocking($pReadHandle, 0);
$succ &= stream_set_blocking($pWriteHandle, 0);
$succ &= stream_set_blocking($cReadHandle, 0);
$succ &= stream_set_blocking($cWriteHandle, 0);
if (!$succ) {
	err('Failed to set streams to non-blocking mode');
}

/*// parse headers from program output
$readRest = false;
while (true) {
	// read line from program output
	$l = fgets($pPipes[1]);
	if ($l === false) {
		if (@fpassthru($pPipes[1]) === false) {
			err('Failed to read from process output');
		}
	}

	// remove line end characters
	$l = preg_replace("/[\r\n]+/", '', $l);

	if ($l !== '') {
		// assume header output from process
		header($l, true);
	} else {
		// assume header and body separator
		$readRest = true;
		break;
	}
}

// pass program stderr output to browser
fpassthru($pPipes[2]);

// pass rest of program stdout output to browser
if ($readRest) fpassthru($pPipes[1]);*/

try {

	// keep track of client and program read- and write-readiness
	$cRead = $cWrite = $pRead = $pWrite = false;
	// buffer for client to program data
	$bufC2P = '';
	// buffer for program to client data
	$bufP2C = '';
	// flag for to know if headers (program -> client) must still be handled
	$headersSent = false;

	while (true) {
		$rHandles = array();
		$wHandles = array();
		$eHandles = array();
		
		// if client is not readable, add it's read-iness to be checked
		if (!$cRead) $rHandles[] = $cReadHandle;
		// if program is not readable, add it's read-iness to be checked
		if (!$pRead) $rHandles[] = $pReadHandle;
		// if client is not writeable, add it's write-ness to be checked
		if (!$cWrite) $wHandles[] = $cWriteHandle;
		// if program is not writeable, add it's write-ness to be checked
		if (!$pWrite) $wHandles[] = $pWriteHandle;
		
		// do the checking
		if (count($rHandles) + count($wHandles) > 0) {
			if (stream_select($rHandles, $wHandles, $eHandles, null) === false) {
				throw new Exception('stream_select failed');
			}
		}
		
		// if client is readable
		if ($cRead || array_search($cReadHandle, $rHandles, true)) {
			$read = fread($cReadHandle, 1024);
			$cRead = false;
			if ($read != false) $bufC2P .= $read;
		}
		
		// if program is readable
		if ($pRead || array_search($pReadHandle, $rHandles, true)) {
			$read = fread($pReadHandle, 1024);
			$pRead = false;
			if ($read != false) $bufP2C .= $read;
		}
		
		// if client is writeable
		if ($cWrite || array_search($cWriteHandle, $wHandles, true)) {
			if (strlen($bufP2C) > 0) {
				// buffer contains data to be sent
				if (!$headersSent) {
						// handle headers
						$headersEndIdx = strpos($bufP2C, "\r\n\r\n");
						if ($headersEndIdx !== false) {
							// headers entirely in buffer
							$headersStr = substr($bufP2C, 0, $headersEndIdx+2);
							$headers = explode("\n", $headersStr);
							foreach($headers as $h) {
								echo 'header['.rtrim($h).']<br>\n';
							}
							$read = substr($read, $headersEndIdx+4);
						}
					} else {
						// send data normally
				}
			}
		}
		
		// if program is writeable
		if ($pWrite || array_search($pWriteHandle, $wHandles, true)) {
		}
		
	}
	
} catch (Exception $e) {
	err($e->getMessage());
}

/*
// select to check
// - if browser has something to send
// - if program has something to send
// - if browser is ready to receive
// - if program is ready to receive
// if browser has something to send: save
// if program has something to send: save
// if browser is ready to receive: send saved * exceptional header handling
// if program is ready to receive: send saved
// loop until program has shut down it's end of the pipes 
*/

// close program
@fclose($pWriteHandle);
@fclose($pReadHandle);
@fclose($cReadHandle);
@fclose($cWriteHandle);

@proc_terminate($pHandle, 9/*SIGKILL*/);
@proc_close($pHandle);

// -- functions below

function err($msg) {
//	header('dummy', true, 500);
	exit($msg);
}

function setIf(&$arr, $key, &$value) {
	if (!isset($value)) return;
	$arr[$key] = $value;
}


/*
function preparePath() {
	if (!isset($_SERVER['PATH_INFO'])) err('No repo specified');
	$pathInfo = $_SERVER['PATH_INFO'];
	if (strlen($pathInfo) <= 1) err('No repo specified');
	$pathInfo = substr($pathInfo, 1);
	$pathInfo = explode('/', $pathInfo);
	foreach ($pathInfo as $c) {
		if ((strlen($c) < 1) ||
			(strpos($c, '..') !== false) ||
			!preg_match('/[A-Za-z0-9-._&]/', $c)) err('Requested path contains invalid characters');
	}
	return $pathInfo;
}
*/

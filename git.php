<?php
// nice error reporting doggie
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// settings
define('REPODIR', getcwd(). '/.repos');

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
$env = array(
	'GIT_PROJECT_ROOT' => '.'
//	'GIT_HTTP_EXPORT_ALL' => '',
	);
setIf($env, 'PATH_INFO', $_SERVER['PATH_INFO']);
setIf($env, 'REMOTE_USER', $_SERVER['REMOTE_USER']);
setIf($env, 'REMOTE_ADDR', $_SERVER['REMOTE_ADDR']);
setIf($env, 'CONTENT_TYPE', $_SERVER['CONTENT_TYPE']);
setIf($env, 'QUERY_STRING', $_SERVER['QUERY_STRING']);
setIf($env, 'REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);

// in addition, Accept and Content-Type were observed being used by git (smart) http transfer
setIf($env, 'HTTP_ACCEPT', $_SERVER['HTTP_ACCEPT']);
setIf($env, 'CONTENT_LENGTH', $_SERVER['CONTENT_LENGTH']);

// log
//file_put_contents('pgkiss.log', "-- \$_SERVER @ ".date('d.m.y h:m:s').":\n". print_r($_SERVER, true), FILE_APPEND);
//file_put_contents('pgkiss.log', "-- ENV @ ".date('d.m.y h:m:s').":\n". print_r($env, true), FILE_APPEND);
//file_put_contents('pgkiss.log', "-- POST @ ".date('d.m.y h:m:s').":\n". print_r($_POST, true), FILE_APPEND);

// start program
$p = proc_open(
//	'bash --norc --noprofile -c \'git http-backend 2>&1 | tee -a pgkiss.log\'',
	'git http-backend',
	array(
		0 => array('pipe', 'r'),
		1 => array('pipe', 'w',),
		2 => array('pipe', 'w'),
	),
	$pipes,
	REPODIR,
	$env,
	array('bypass_shell' => true)
	);
if ($p === false) err('Failed to execute process');

// write post data from browser to program
fwrite($pipes[0], file_get_contents('php://input'));

// parse headers from program output
$readRest = false;
while (true) {
	// read line from program output
	$l = fgets($pipes[1]);
	if ($l === false) {
		if (@fpassthru($pipes[1]) === false) {
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
fpassthru($pipes[2]);

// pass rest of program stdout output to browser
if ($readRest) fpassthru($pipes[1]);

// close program
@fclose($pipes[0]);
@fclose($pipes[1]);
@fclose($pipes[2]);
@proc_terminate($p, 9/*SIGKILL*/);
@proc_close($p);

// -- functions below

function err($msg) {
	header('dummy', true, 500);
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

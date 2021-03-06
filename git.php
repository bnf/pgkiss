<?php
// i want it all
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// <settings>
// REPODIR = directory under which git repositories reside,
// GITPATH = path to git executable (use 'git' if git is reachable via PATH)

define('REPODIR', getcwd(). '/repos');
define('GITPATH', 'git');

// </settings>

// set environment for the program
$pEnv = array(
	'GIT_PROJECT_ROOT' => REPODIR
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
	GITPATH. ' http-backend',
	array(
		0 => array('pipe', 'r'),
		1 => array('pipe', 'w'),
		2 => array('file', 'err.log', 'a'),
	),
	$pPipes,
	null,
	$pEnv,
	array('bypass_shell' => true) // bypass_shell = windows-only flag
	);
if ($pHandle === false) err('Failed to execute process');

// friendly names for handles to/from program
$pReadHandle = $pPipes[1];
$pWriteHandle = $pPipes[0];

// write client's post data to program
fwrite($pWriteHandle, file_get_contents('php://input'));

// handle headers
while (($l = fgets($pReadHandle)) !== "\r\n") {
	if (strpos($l, 'Status: ') === 0) {
		preg_match('/^Status: ([0-9]+) (.*)$/', $l, $matches);
		header($matches[2], true, $matches[1]);
	} else {
		header(rtrim($l), true);
	}
}

// pass rest of program output to client
fpassthru($pReadHandle);

// line break to make reading easier
file_put_contents('err.log', "\n", FILE_APPEND);

// close pipes
@fclose($pWriteHandle);
@fclose($pReadHandle);

// ensure program is closed
@proc_terminate($pHandle, 9/*SIGKILL*/);
@proc_close($pHandle);

// -- functions below

function err($msg) {
	exit($msg);
}

// $arr[$key] = $value IF isset($value)
function setIf(&$arr, $key, &$value) {
	if (isset($value)) $arr[$key] = $value;
}

/* [LGPL] Copyright 2012 Gima
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

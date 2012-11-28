<?php
$info = array();
foreach ($_SERVER as $key => $val) {
	if (preg_match('/^REDIRECT_/', $key)) $info[$key] = $val;
}
file_put_contents('pgkiss.log', "-- 404 @ ". date('d.m.y h:m:s').":". print_r($info, true), FILE_APPEND);

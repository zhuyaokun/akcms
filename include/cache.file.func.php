<?php
if(!defined('CORE_ROOT')) exit;
define('CACHEPATH', AK_ROOT.'cache/');
function getcache($key, $expire = 0) {
	$filename = calcachefilename($key);
	if(!is_readable($filename)) {
		if($key == 'settings') {
			updatecache();
			return getcache($key, $expire);
		}
		return false;
	}
	$_cache = readfromfile($filename);
	$return = unserialize($_cache);
	if($return === false) return false;
	if($expire) $return = array('time' => ak_filetime($filename),'value' => $return['value']);
	return $return;
}

function setcache($key, $value, $expire = 0) {
	$filename = calcachefilename($key);
	if($expire) $value = array('value' => $value);
	$_c = serialize($value);
	return writetofile($_c, $filename);
}

function touchcache($key) {
	$filename = calcachefilename($key);
	return touch($filename);
}

function expirecache($key) {
	$filename = calcachefilename($key);
	if(file_exists($filename)) @touch($filename, 0);
}

function deletecache($key) {
	$filename = calcachefilename($key);
	if(file_exists($filename)) unlink($filename);
}

function calcachefilename($key) {
	$character = array('/', '\\', '?', ':', '"', '>', '<', '|', '*');
	foreach($character as $c) {
		$key = str_replace($c, '#', $key);
	}
	$filename = CACHEPATH.$GLOBALS['codekey'].'_'.$key.'.txt';
	return $filename;
}
?>
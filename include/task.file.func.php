<?php
if(!defined('CORE_ROOT')) exit;
define('TASKFILE', AK_ROOT.'cache/tasks/[key]');
define('TASKFILEOFFSET', AK_ROOT.'cache/tasks/[key].offset');
if(!isset($cc_pid_path)) $cc_pid_path = AK_ROOT.'cache/pids';

createpathifnotexists(AK_ROOT."cache/tasks");



function gettask($key, $num = 0, $test = 0) {
	global $pid, $cc_pid_path;
	if($num == 0) {
		$onereturn = 1;
		$num = 1;
	} elseif($num == -1) {
		$num = PHP_INT_MAX;
	}
	if(substr($key, 0, 1) === '*') {
		$key = substr($key, 1);
		$cc = 1;
		createpathifnotexists($cc_pid_path);
		createpathifnotexists("$cc_pid_path/$key");
	}
	$file = str_replace('[key]', $key, TASKFILE);
	if(!file_exists($file)) {
		akunlink("$cc_pid_path/$key/$pid");
		return false;
	}
	$offsetfile = str_replace('[key]', $key, TASKFILEOFFSET);
	if(!file_exists($offsetfile)) touch($offsetfile);
	$task = '';
	($fo = @fopen($offsetfile, 'r+')) || ($fo = @fopen($offsetfile, 'w'));
	flock($fo, LOCK_EX);
	fseek($fo, 0);
	$offset = fgets($fo);
	$offset = (int)$offset;
	if(!$fp = fopen($file, 'r')) {
		flock($fo, LOCK_UN);
		fclose($fo);
		akunlink($file);
		akunlink($offsetfile);
		akunlink("$cc_pid_path/$key/$pid");
		return '';
	}
	fseek($fp, $offset);
	$i = 0;
	$tasks = array();
	while(!feof($fp)) {
		$task = fgets($fp);
		$offset += strlen($task);
		$task = substr($task, 0, -1);
		if($task != '') {
			$i ++;
			$tasks[] = $task;
			if($i >= $num) break;
		}
	}
	fclose($fp);
	if(empty($test)) {
		rewind($fo);
		fwrite($fo, $offset);
		flock($fo, LOCK_UN);
	}
	fclose($fo);
	if(empty($tasks)) {
		@akunlink($file);
		@akunlink($offsetfile);
		akunlink("$cc_pid_path/$key/$pid");
		return false;
	} else {
		if(!empty($pid)) ak_touch("$cc_pid_path/$key/$pid");
		foreach($tasks as $k => $v) {
			$v = str_replace('#\n#', "\n", $v);
			if(substr($v, 0, 2) == 'a:') $v = unserialize($v);
			$tasks[$k] = $v;
		}
		if(!isset($onereturn)) {
			return $tasks;
		} else {
			return current($tasks);
		}
	}
}

function addtask($key, $task) {
	if(substr($key, 0, 1) === '*') $key = substr($key, 1);
	$file = str_replace('[key]', $key, TASKFILE);
	$offsetfile = str_replace('[key]', $key, TASKFILEOFFSET);
	if(!file_exists($offsetfile)) touch($offsetfile);
	$fp = fopen($file, 'a');
	flock($fp, LOCK_EX);
	if(is_array($task)) $task = serialize($task);
	$task = str_replace("\n", '#\n#', $task);
	fwrite($fp, $task."\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}

function gettaskps($key) {
	global $cc_pid_path, $pid;
	if($pid == 0) return 0;
	if(substr($key, 0, 1) === '*') $key = substr($key, 1);
	if(!file_exists("$cc_pid_path/$key")) return 0;
	$fp = opendir("$cc_pid_path/$key");
	$count = 0;
	while(false !== ($file = readdir($fp))) {
		if($file === '.' || $file === '..') continue;
		$_file = "$cc_pid_path/$key/$file";
		
		if(thetime() - filemtime($_file) > 600) {
			akunlink($_file);
		} else {
			$count ++;
		}
	}
	return $count;
}

function addtasks($key, $tasks) {
	if(empty($tasks)) return false;
	$file = str_replace('[key]', $key, TASKFILE);
	$offsetfile = str_replace('[key]', $key, TASKFILEOFFSET);
	if(!file_exists($offsetfile)) touch($offsetfile);
	$fp = fopen($file, 'a');
	flock($fp, LOCK_EX);
	foreach($tasks as $task) {
		if(is_array($task)) $task = serialize($task);
		$task = str_replace("\n", '#\n#', $task);
		fwrite($fp, $task."\n");
	}
	flock($fp, LOCK_UN);
	fclose($fp);
}

function gettaskpercent($key) {
	$file = str_replace('[key]', $key, TASKFILE);
	$offsetfile = str_replace('[key]', $key, TASKFILEOFFSET);
	
	if(!file_exists($file)) return 100;
	
	$total = filesize($file);
	if(!file_exists($offsetfile)) return 0;
	$current = readfromfile($offsetfile);
	if($current >= $total) {
		@akunlink($file);
		@akunlink($offsetfile);
		return 99.99;
	}
	return min(99.99, nb($current * 100 / $total));
}

function deletetask($key) {
	$file = str_replace('[key]', $key, TASKFILE);
	$offsetfile = str_replace('[key]', $key, TASKFILEOFFSET);
	@akunlink($offsetfile);
	@akunlink($file);
}
?>
<?php
if(!defined('CORE_ROOT')) exit;

function setkeyvalue($key, $value) {
	global $db, $thetime;
	$db->replaceinto('keys', array('key' => $key, 'value' => $value, 'dateline' => $thetime), 'key');
}

function getkeyvalue($key) {
	global $db;
	$record = $db->get_by('*', 'keys', "`key` = '".$db->addslashes($key)."'");
	if(empty($record)) return false;
	$db->update('keys', array('hits' => '+1'), "id='{$record['id']}'");
	return $record['value'];
}
?>
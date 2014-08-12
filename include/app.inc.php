<?php
if(!defined('CORE_ROOT')) exit;
define('APP_PATH', AK_ROOT.'configs/apps/'.$app.'/');
if(!file_exists(APP_PATH)) {
	confirm($lan['firstrunapp'], 'index.php?file=app&action=install&key='.$app.'&autorun=1', 'index.php?file=welcome');
}
?>
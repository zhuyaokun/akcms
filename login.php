<?php
if(!defined('CORE_ROOT')) exit();
require_once CORE_ROOT.'include/admin.inc.php';
if(isset($post_loginsubmit)) {
	if($editor = $db->get_by('*', 'admins', "editor='".$db->addslashes($post_username)."'")) {
		if(ak_md5($post_password, 0, 2) == $editor['password']) {
			if($editor['freeze'] == 1) adminmsg($lan['youarefreeze'], 'index.php', 3, 1);
			if(!empty($post_rememberlogin)) {
				setlogin($post_username, $thetime + 24 * 3600 * 365 * 10);
			} else {
				setlogin($post_username);
			}
			$target = 'index.php';
			if(ifthemeuninstalled()) $target = 'index.php?file=theme&action=themeinstall';
			adminmsg($lan['login_success'], $target);
		} else {
			adminmsg($lan['login_failed'], 'index.php?file=login', 3, 1);
		}
	} else {
		adminmsg($lan['login_failed'], 'index.php?file=login', 3, 1);
	}
} else {
	displaytemplate('login.htm');
}
?>
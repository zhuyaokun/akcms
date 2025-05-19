<?php
if(!defined('CORE_ROOT')) @include 'include/directaccess.php';
require_once CORE_ROOT.'include/admin.inc.php';
if($get_action == 'changepassword') {
	if(isset($get_submit)) {
		if($post_newpassword != $post_newpassword2) adminmsg($lan['repeatpassworderror'], 'back', 3, 1);
		if(empty($post_oldpassword) || empty($post_newpassword)) adminmsg($lan['passwordempty'], 'back', 3, 1);
		if($user = $db->get_by('*', 'admins', "editor='$admin_id'")) {
			if($user['password'] != ak_md5($post_oldpassword, 0, 2)) adminmsg($lan['oldpassworderror'], 'back', 3, 1);
			$newpassword = ak_md5($post_newpassword, 0, 2);
			$db->update('admins', array('password' => $newpassword), "editor='$admin_id'");
			adminmsg($lan['operatesuccess']);
		} else {
			adminmsg($lan['nothisuser'], 'back', 3, 1);
		}
	} else {
		displaytemplate('admincp_changepass.htm');
	}
} elseif($get_action == 'manageaccounts') {
	checkcreator();
	if(!isset($get_job)) {
		$query = $db->query_by('*', 'admins', '', 'id');
		$str_users = '';
		while($user = $db->fetch_array($query)) {
			if($user['editor'] != 'admin') {
				$status = empty($user['freeze']) ? available($lan['active']) : disabled($lan['frozen']);
				$changestatus = empty($user['freeze']) ? "<a href=\"index.php?file=account&action=manageaccounts&vc={$vc}&job=freeze&id={$user['id']}\">{$lan['freeze']}</a>" : "<a href=\"index.php?file=account&action=manageaccounts&vc={$vc}&job=active&id={$user['id']}\">{$lan['activate']}</a>";
				$reset = "<a href=\"index.php?file=account&action=manageaccounts&vc={$vc}&job=reset&id={$user['id']}\">".alert($lan['reset'])."</a>";
				if($user['items'] == 0) {
					$delete = "<a href=\"index.php?file=account&action=manageaccounts&job=delete&vc={$vc}&editor={$user['editor']}\">".alert($lan['delete'])."</a>";
				} else {
					$delete = "-";
				}
			} else {
				$status = available($lan['active']);
				$changestatus = '-';
				$reset = '-';
				$delete = "-";
			}
			$str_users .= "<tr>
			<td>{$user['editor']}</td>
			<td>{$delete}</td>
			<td>{$status}</td>
			<td>{$changestatus}</td>
			<td>{$reset}</td>
			<td class=\"mininum\">{$user['items']}</td>
			</tr>";
		}
		$smarty->assign('users', $str_users);
		displaytemplate('admincp_manageaccounts.htm');
	} elseif($get_job == 'newaccount') {
		if(empty($post_account) || empty($post_password)) adminmsg($lan['accountorpasswordempty'], 'back', 3, 1);
		if($db->get_by('*', 'admins', "editor='$post_account'")) adminmsg($lan['accountexist'], 'back', 3, 1);
		$value = array(
			'editor' => $post_account,
			'password' => ak_md5($post_password, 0, 2)
		);
		$db->insert('admins', $value);
		adminmsg($lan['accoundpassword']."{$post_account}/{$post_password}<br>".$lan['operatesuccess'], 'index.php?file=account&action=manageaccounts');
	} elseif($get_job == 'freeze' || $get_job == 'active') {
		vc();
		$array_admins_status = array(
			'freeze' => 1,
			'active' => 0
		);
		if(empty($get_id) || $get_id == 1) adminmsg($lan['parameterwrong'], 'back', 3, 1);
		$db->update('admins', array('freeze' => $array_admins_status[$get_job]), "id='$get_id'");
		adminmsg($lan['operatesuccess'], 'index.php?file=account&action=manageaccounts');
	} elseif($get_job == 'delete') {
		vc();
		if(empty($get_editor) || $get_editor == 'admin') adminmsg($lan['parameterwrong'], 'back', 3, 1);
		if($db->get_by('*', 'items', "author='$get_editor'")) adminmsg($lan['accounthasitems'], 'back', 3, 1);
		$db->delete('admins', "editor='$get_editor'");
		adminmsg($lan['operatesuccess'], 'index.php?file=account&action=manageaccounts');
	} elseif($get_job == 'reset') {
		vc();
		$default_password = 'akcms';
		if(empty($get_id) || $get_id == 1) adminmsg($lan['parameterwrong'], 'back', 3, 1);
		$password = ak_md5($default_password, 0, 2);
		$db->update('admins', array('password' => $password), "id='$get_id'");
		adminmsg($lan['passwordreset'], 'index.php?file=account&action=manageaccounts');
	}
}elseif($get_action == 'logout') {
	setcookie('auth', '');
	aksetcookie('auth', '');
	adminmsg($lan['logout_success'], 'index.php?file=login');
} else {
	adminmsg($lan['nodefined'], '', 0, 1);
}
runinfo();
aexit();
?>
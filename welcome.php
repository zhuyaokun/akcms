<?php
if(!defined('CORE_ROOT')) @include 'include/directaccess.php';
require CORE_ROOT.'include/admin.inc.php';
!isset($get_action) && $get_action = '';
if($get_action == 'phpinfo') {
	if(function_exists('phpinfo')) {
		phpinfo();exit;
	} else {
		exit('phpinfo() is disabled');
	}
} elseif($get_action == 'checkwritable') {
	$array_files = array(
		FORE_ROOT,
		'cache',
		'templates',
		'cache/templates',
		'cache/foretemplates',
		'configs',
	);
	$message = '';
	foreach($array_files as $file) {
		if(!is_writable($file)) $message .= '"'.$file.'"'.$lan['isunwritable'].'<br>';
	}
	if(!empty($message)) {
		adminmsg($lan['writableerror'].'<br>'.$message, 'index.php?file=welcome', 3, 1);
	} else {
		adminmsg($lan['writableok'], 'index.php?file=welcome');
	}
} elseif($get_action == 'optimize') {
	if(strpos($dbtype, 'sqlite') !== false) adminmsg($lan['sqliteunsupport'], 'index.php?file=welcome', 3, 1);
	$db->query("OPTIMIZE TABLE `{$tablepre}_admins` , `{$tablepre}_attachments` , `{$tablepre}_categories` , `{$tablepre}_filenames` , `{$tablepre}_items` , `{$tablepre}_sections` , `{$tablepre}_settings` , `{$tablepre}_texts` , `{$tablepre}_variables`");
	adminmsg($lan['operatesuccess'], 'index.php?file=welcome');
} elseif($get_action == 'updatecache') {
	updatecache();
	deletecache('categoryselect');
	adminmsg($lan['operatesuccess'], 'index.php?file=welcome');
} elseif($get_action == 'copyfront') {
	createfore();
	adminmsg($lan['operatesuccess'], 'index.php?file=welcome');
} elseif($get_action == 'runsql') {
	if(isset($post_sql)) {
		$query = $db->query($post_sql);
		$body = '';
		if($query !== true && !empty($query)) {
			while($result = $db->fetch_array($query)) {
				if(empty($header)) {
					$header = '<tr class="header">';
					foreach(array_keys($result) as $field) {
						$header .= '<td>'.ak_htmlspecialchars($field).'</td>';
					}
					$header .= '</tr>';
				}
				$body .= "<tr>";
				foreach($result as $value) {
					$body .= "<td>".ak_htmlspecialchars($value)."</td>";
				}
				$body .= "</tr>";
			}
			if($body != '') {
				$result = "<table class=\"commontable\" cellspacing=\"1\" cellpadding=\"4\">{$header}{$body}</table><br>";
				$smarty->assign('result', $result);
			}
		}
		$smarty->assign('sql', $post_sql);
	}
	displaytemplate('admincp_runsql.htm');
} elseif($get_action == 'updatefilenames') {
	if(empty($get_process)) {
		if(empty($get_step)) {
			$itemcount = $db->get_by('COUNT(*) as c', 'items');
			$categorycount = $db->get_by('COUNT(*) as c', 'categories');
			$smarty->assign('datacount', $itemcount + $categorycount);
			displaytemplate('updatefilenames.htm');
			aexit();
		} else {
			require_once CORE_ROOT.'include/task.file.func.php';
			aksetcookie($get_action.'step', $get_step);
			aksetcookie($get_action.'stepwait', $get_stepwait);
			$query = $db->query_by('id', 'items');
			while($item = $db->fetch_array($query)) {
				addtask($get_action, 'item:'.$item['id']);
			}
			$query = $db->query_by('id', 'categories');
			while($category = $db->fetch_array($query)) {
				addtask($get_action, 'category:'.$category['id']);
			}
			header('location:?file=welcome&action=updatefilenames&process=1&frame=1');
		}
	} else {
		if(!empty($get_frame)) {
			$stepwait = akgetcookie($get_action.'stepwait');
			showprocess($lan['updatefilenames'], '?file=welcome&action=updatefilenames&process=1', '', $stepwait);
		} else {
			require_once CORE_ROOT.'include/task.file.func.php';
			$step = akgetcookie($get_action.'step');
			$tasks = gettask($get_action, $step);
			$items = $categories = array();
			$status = '';
			if(!empty($tasks)) {
				foreach($tasks as $task) {
					list($type, $id) = explode(':', $task);
					if($type == 'item') {
						$items[] = $id;
					} else {
						$categories[] = $id;
					}
				}
				if(!empty($items)) {
					$ids = implode(',', $items);
					$query = $db->query_by('*', 'items', "id IN ($ids)");
					while($item = $db->fetch_array($query)) {
						$status = $item['title'];
						$filename = itemhtmlname($item['id'], 1, $item);
						if(empty($filename)) continue;
						$value = array(
							'filename' => $filename,
							'type' => 'item',
							'dateline' => $thetime,
							'id' => $item['id'],
							'page' => 0
						);
						$db->update('filenames', $value, "id='{$item['id']}' AND type='item'");
						$db->insert('filenames', $value);
					}
				}
			}
			$percent = gettaskpercent($get_action);
			aexit($percent."\t\t$status");
		}
	}
	exit;
	$db->delete('filenames');
	if(!empty($setting_usefilename)) {
		$query = $db->query_by('*', 'items');
		while($item = $db->fetch_array($query)) {
			$filename = itemhtmlname($item['id'], 1, $item);
			if(empty($filename)) continue;
			$value = array(
				'filename' => $filename,
				'type' => 'item',
				'dateline' => $thetime,
				'id' => $item['id'],
				'page' => 0
			);
			$db->insert('filenames', $value);
		}
	}
	$query = $db->query_by('*', 'categories', '1', 'categoryup,id');
	while($category = $db->fetch_array($query)) {
		$extvalue = updatecategoryextvalue($category['id'], $category);
		$value = array(
			'filename' => $extvalue['fullpath'],
			'type' => 'category',
			'dateline' => $thetime,
			'id' => $category['id']
		);
		$db->insert('filenames', $value);
	}
	adminmsg($lan['operatesuccess']);
} elseif($get_action == 'checknew') {
	include(CORE_ROOT.'repair.php');
	$apiurl = 'http://www.akhtm.com/api/checknew.php?ver='.$sysedition;
	$result = readfromurl($apiurl);
	$apiurl = 'http://www.akhtm.com/api/changelog3.php?ver='.$sysedition.'&akpath=http://'.$_SERVER['HTTP_HOST'].substr(AK_URL, 0, -1);
	if($result == '1') {
		aexit("$('#newversion').html('<a href=\"$apiurl\" target=\"_self\">{$lan['findnew']}</a>');");
	}
} elseif($get_action == 'phpmodules') {
	$options = array();
	$options['curl'] = $options['mb'] = $options['iconv'] = $options['mem'] = $options['gd'] = 0;
	if(function_exists('curl_init') && function_exists('curl_exec')) $options['curl'] = 1;
	if(function_exists('mb_strpos')) $options['mb'] = 1;
	if(function_exists('iconv')) $options['iconv'] = 1;
	if(function_exists('memory_get_usage')) $options['mem'] = 1;
	if(function_exists('gd_info')) $options['gd'] = 1;
	$smarty->assign('options', $options);
	displaytemplate('phpmodules.htm');
} elseif($get_action == 'checkauth') {
	touch(AK_ROOT.'configs/auth.php');
	akheader('location:index.php?file=welcome');
} elseif($get_action == 'installtheme') {
} else {
	if(!empty($_GET['updated'])) writetofile("<?php//{$sysedition}?>", AK_ROOT.'configs/version.php');
	$theme = '';
	if(isset($setting_theme)) $theme = $setting_theme;
	$infos = getcache('infos');
	$servertime = date('Y-m-d H:i:s', time());
	$correcttime = date('Y-m-d H:i:s', $thetime);
	isset($_ENV['TERM']) && $os = $_ENV['TERM'];
	$max_upload = ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'Disabled';
	$maxexetime = ini_get('max_execution_time');
	$smarty->assign('items', $infos['items']);
	$smarty->assign('pvs', $infos['pvs']);
	$smarty->assign('editors', $infos['editors']);
	$smarty->assign('attachmentsizes', empty($infos['attachmentsizes']) ? 0 : $infos['attachmentsizes']);
	$smarty->assign('attachments', $infos['attachments']);
	$smarty->assign('admin_id', $admin_id);
	$smarty->assign('os', $os);
	$smarty->assign('phpversion', PHP_VERSION);
	$smarty->assign('dbversion', $db->version());
	$smarty->assign('akversion', $sysedition);
	$smarty->assign('theme', $theme);
	$smarty->assign('authsuccess', $authsuccess);
	$smarty->assign('iscreator', iscreator());
	$smarty->assign('maxupload', $max_upload);
	$smarty->assign('maxexetime', $maxexetime);
	$smarty->assign('servertime', $servertime);
	$smarty->assign('correcttime', $correcttime);
	$smarty->assign('dbtype', $dbtype);
	displaytemplate('admincp_welcome.htm');
}
runinfo();
aexit();
?>
<?php
if(!defined('CORE_ROOT')) exit;

function ifthemeuninstalled() {
	global $settings;
	if(!file_exists(AK_ROOT.'install/install.php')) return false;
	if(file_exists(AK_ROOT.'configs/themeinstall.lock')) return false;
	return true;
}

function themeinstalled() {
	ak_rmdir(AK_ROOT.'install/configs');
	ak_rmdir(AK_ROOT.'install/db');
	ak_rmdir(AK_ROOT.'install/systemplates');
	ak_rmdir(AK_ROOT.'install/templates');
	ak_rmdir(AK_ROOT.'install/trunk');
	akunlink(AK_ROOT.'install/custom.config.php');
	akunlink(AK_ROOT.'install/install.php');
	akunlink(AK_ROOT.'install/update.log');
	touch(AK_ROOT.'configs/themeinstall.lock');
}

function ifinstalltemplate() {
	global $get_installtemplate, $settings;
	if(!empty($get_installtemplate)) return 1;
	if(getcache('installtemplate')) return 0;
	if(empty($settings['noinstalltemplate'])) return 1;
	return 0;
}

function verifysiteid() {
	global $setting_siteid;
	if(strlen($setting_siteid) != 6) return false;
	$string1 = substr($setting_siteid, 0, 2);
	$string2 = substr($setting_siteid, 2, 2);
	$publicip = publicip();
	if($string1 != substr(md5($publicip), 0, 2)) return false;
	if($string2 != substr(md5(AK_ROOT), 0, 2)) return false;
	return true;
}

function resetsiteid() {
	$siteid = generatesiteid();
	setsetting('siteid', $siteid);
	updatecache('settings');
}

function noinstalltemplate() {
	global $db;
	$db->replaceinto('settings', array('variable' => 'noinstalltemplate', 'value' => 1), 'variable');
	updatecache('settings');
}

function rendersettinghtml($name, $title, $description, $value = '', $type = '', $standby = '') {
	$input = renderinput($name, $type, $standby, $value);
	return "<tr><td valign=\"top\"><b>{$title}</b><br>".htmlspecialchars($description)."</td><td valign=\"top\" width=\"300\">$input</td></tr>\n";
}

function checkcreator() {
	global $lan, $admin_id;
	if(iscreator($admin_id) != 1) adminmsg($lan['forcreatoronly'], '', 0, 1);
}

function ifallowtheme() {
	if(file_exists(AK_ROOT.'configs/theme.lock')) return false;
	return true;
}

function preparetheme() {
	@akunlink(AK_ROOT.'configs/theme.lock');
}
function finishtheme() {
	@ak_touch(AK_ROOT.'configs/theme.lock');
}

function ifcustomed() {
	if(file_exists(AK_ROOT.'configs/custom.menu.xml')) return true;
	return false;
}
function iscreator($id = '') {
	if($id == '') $id = $GLOBALS['admin_id'];
	if(strtolower($id) == 'admin') return 1;
	return 0;
}
function vc() {
	if(empty($_GET['vc']) || $GLOBALS['_vc'] != $_GET['vc']) aexit('error');
}
function go($url, $crumb = 1) {
	if($crumb) {
		$join = '&';
		if(strpos($url, '?') === false) $join = '?';
		header("location:{$url}{$join}crumb=".random(6));
	} else {
		header("location:{$url}");
	}
	aexit();
}

function adminmsg($message, $url_forward = '', $timeout = 1, $flag = 0) {
	global $header_charset;
	if($flag == 0) {
		$flag = 'info';
	} else {
		$flag = 'warning';
	}
	if($url_forward == 'back') {
		$url_forward = $_SERVER['HTTP_REFERER'];
	} else {
		$and = '&';
		if(strpos($url_forward, '?') === false) $and = '?';
		if($url_forward != '') $url_forward .= $and.'r='.random(6);
	}
	$variable = array();
	$variable['flag'] = $flag;
	$variable['message'] = $message;
	$variable['url_forward'] = $url_forward;
	$variable['timeout'] = $timeout;
	$variable['timeout_micro'] = $timeout * 1000;
	displaytemplate('message.htm', $variable);
	runinfo();
	aexit();
}

function get_select($type, $root = 0) {
	if($type == 'category') {
		return rendercategoryselect();
	} elseif($type == 'section') {
		global $sections;
		$selectsections = '';
		foreach($sections as $section) {
			$selectsections .= "<option value=\"$section[id]\">".ak_htmlspecialchars($section['section'])."</option>\n";
		}
		return $selectsections;
	} elseif($type == 'modules') {
		global $modules;
		$modules = getcache('modules');
		$selectmodules = '';
		foreach($modules as $module) {
			$selectmodules .= "<option value=\"{$module['id']}\">".$module['modulename']."</option>\n";
		}
		return $selectmodules;
	}
}

function get_select_templates() {
	global $templates;
	$templates = getcache('templates');
	$selecttemplates = '';
	foreach($templates as $template) {
		$selecttemplates .= "<option value=\"$template\">".$template."</option>\n";
	}
	return $selecttemplates;
}

function managesetting($config) {
	global $db, $lan, $currenturl, $vc;
	$do = httpget('do');
	$vs = array_keys($config['variables']);
	$settings = array();
	foreach($config['variables'] as $variable => $value) {
		if($v = $db->get_by('*', 'settings', "variable='$variable'")) $settings[$variable] = $v;
		$lan["s_$variable"] = $value;
	}
	if($do == '' || $do == 'setting') {
		$html = inputshow($settings, $vs);
		$params = array(
			'name' => $config['name'],
			'html' => $html,
			'formaction' => $currenturl,
			'return' => $config['return'],
			'returnurl' => $config['returnurl']
		);
		displaytemplate('common_setting.htm', $params);
	} elseif($do == 'savesetting') {
		foreach($vs as $variable) {
			if(!isset($settings[$variable]) || $_POST[$variable] != $settings[$variable]['value']) {
				setsetting($variable, $_POST[$variable]);
			}
		}
		adminmsg($lan['operatesuccess'], 'back');
	}
}

function managedbtable($config) {
	global $db, $lan, $currenturl, $vc;
	$do = httpget('do');
	$table = $config['table'];
	$primary = $config['primary'];
	
	if($do == '' || $do == 'manage') {
		if(!isset($config['pagenumsize'])) $config['pagenumsize'] = 15;
		$page = 1;
		if(isset($_GET['page'])) $page = $_GET['page'];
		$where = '1';
		if(isset($config['where'])) $where = $config['where'];
		
		$htmltop = "";
		$fieldnum = count($config['fields']);
		
		$sort = 'id DESC';
		if(isset($_GET['orderby'])) $sort = $db->addslashes($_GET['orderby']);

		$sorthtml = '';
		if(!empty($config['sorts'])) {
			foreach($config['sorts'] as $key => $sortname) {
				$sorthtml .= "<option value='$key'>$sortname</option>";
			}
			$sorthtml = $lan['orderby']."&nbsp;<select id='orderby' name='orderby'>$sorthtml</select>&nbsp;<script>$(document).ready(function(){\$('#orderby').val('$sort');});</script>";
		}
		if(empty($config['noadd']) || !empty($config['functionbutton'])) {
			$htmltop .= "<div style='float:right;text-align:right;'>";
			if(empty($config['noadd'])) $htmltop .= "&nbsp;<input type='button' value='{$lan['add']}' onclick='location=\"$currenturl&do=add\"' />";
			if(!empty($config['functionbutton'])) {
				foreach($config['functionbutton'] as $text => $url) {
					$htmltop .= "&nbsp;<input type='button' value='{$text}' onclick='location=\"$url\"' />";
				}
			}
			$htmltop .= "</div>";
		}
		if(!empty($config['filters'])) {
			foreach($config['filters'] as $filter => $value) {
				if(empty($value['size'])) $value['size'] = '120px';
				$_v = isset($_GET[$filter]) ? $_GET[$filter] : '';
				$htmltop .= $value['name']."&nbsp;<input type='text' style='width:{$value['size']}' name='{$filter}' value='".htmlspecialchars($_v)."' />&nbsp;";
				if($_v == '') continue;
				if($value['type'] == 'equal') {
					$where .= " AND {$filter}='".$db->addslashes($_GET[$filter])."'";
				} elseif($value['type'] == 'like') {
					$where .= " AND {$filter} LIKE '%".$db->addslashes($_GET[$filter])."%'";
				}
			}
			$htmltop .= "$sorthtml<input type='submit' value='{$lan['query']}' />";
			if(isset($_GET['file'])) $htmltop .= "<input type='hidden' name='file' value='{$_GET['file']}' />";
			if(isset($_GET['action'])) $htmltop .= "<input type='hidden' name='action' value='{$_GET['action']}' />";
			if(isset($_GET['app'])) $htmltop .= "<input type='hidden' name='app' value='{$_GET['app']}' />";
			$htmltop .= "</form>";
			
			
		}
		if($htmltop != "") {
			$htmltop = "<table width='100%' border='0' align='center' cellpadding='4' cellspacing='1' class='commontable'><tr class='header'><td colspan=''><div class='righttop'></div>{$lan['manage']}{$config['name']}</td></tr><tr><td><form action='index.php'>$htmltop</td></tr></table>";
		}

		$sql = "SELECT COUNT(*) as c FROM $table WHERE {$where}";
		$count = $db->get_one($sql);
		
		$start = ($page - 1) * $config['pagenumsize'];
		$sql = "SELECT * FROM $table WHERE {$where} ORDER BY $sort LIMIT $start,{$config['pagenumsize']}";
		
		$str_index = multi($count['c'], $config['pagenumsize'], $page, $currenturl);
		$query = $db->query($sql);

		$table = '<table width="100%" border="0" align="center" cellpadding="4" cellspacing="1" class="commontable"><tr class="header">';
		foreach($config['fields'] as $field => $name) {
			if(is_array($name)) $name = $name['name'];
			$table .= "<td>{$name}</td>";
		}
		if(!empty($config['operates'])) $table .= "<td>&nbsp;</td>";
		$table .= "</tr>";
		$rows = array();
		while($row = $db->fetch_array($query)) {
			if(!empty($config['itemexteval'])) {
				$ext = eval($config['itemexteval']);
				$row += $ext;
			}
			$rows[] = $row;
		}
		foreach($rows as $row) {
			$tr = '<tr>';
			foreach($config['fields'] as $field => $_v) {
				$value = '';
				if(isset($row[$field])) $value = $row[$field];
				if(!is_array($_v)) {
					$tr .= "<td>{$value}</td>";
				} elseif(!empty($_v['function']) && function_exists($_v['function'])) {
					$_function = $_v['function'];
					$value = $_function($value, $row);
					$tr .= "<td>{$value}</td>";
				} elseif(isset($_v['eval'])) {
					$_e = $_v['eval'];
					$_e = str_replace('[value]', $value, $_e);
					$result = eval($_e);
					$tr .= "<td>$result</td>";
				} elseif(isset($_v['url'])) {
					$url = $_v['url'];
					$url = str_replace('[id]', $row['id'], $url);
					$url = str_replace('[vc]', $vc, $url);
					$url = mergeurl($url, $currenturl);
					$_h = isset($_v['template']) ? $_v['template'] : '<a href="[url]">[value]</a>';
					$_h = str_replace('[url]', $url, $_h);
					$_h = str_replace('[value]', $value, $_h);
					$tr .= "<td>$_h</td>";
				} elseif(isset($_v['template'])) {
					$_h = $_v['template'];
					$_h = str_replace('[value]', $value, $_h);
					$tr .= "<td>$_h</td>";
				} else {
					$tr .= "<td>{$value}</td>";
				}
			}
			if(!empty($config['operates'])) {
				$operatehtml = '';
				foreach($config['operates'] as $operate => $url) {
					$url = str_replace('[id]', $row['id'], $url);
					$url = str_replace('[vc]', $vc, $url);
					if(substr($url, -1) == '@') {
						$url = substr($url, 0, -1);
						$url = mergeurl($url, $currenturl);
						$operatehtml .= "<a href='javascript:popup(\"{$url}\")'>{$operate}</a>&nbsp;";
					} else {
						$url = mergeurl($url, $currenturl);
						$operatehtml .= "<a href='{$url}'>{$operate}</a>&nbsp;";
					}
				}
				$tr .= "<td>$operatehtml</td>";
			}
			$tr .= "</tr>";
			$table .= $tr;
		}
		if(!isset($tr)) $table .= "<tr><td colspan='".($fieldnum + 1)."'>{$lan['item_no']}</td></tr>";
		$table .= "<tr class='header'><td colspan='".($fieldnum + 1)."'>$str_index</td></tr>";
		$table .= "</table>";
		displaytemplate('admincp_managetable.htm', array("table" => $table, 'htmlfilter' => $htmltop));
	} elseif($do == 'add') {
		if(!empty($config['noadd'])) aexit('error');
		if(empty($_POST)) {
			$table = '<table width="500" border="0" align="center" cellpadding="4" cellspacing="1" class="commontable"><tr class="header"><td colspan="2"><div class="righttop"><a href="'.$currenturl.'&do=manage">'.$lan['manage'].$config['name'].'&nbsp;&gt;&gt;</a></div>'.$lan['add'].$config['name'].'</td></tr>';
			if(!empty($config['addfields'])) {
				foreach($config['addfields'] as $key => $field) {
					$name = isset($field['name']) ? $field['name'] : $key;
					isset($field['style']) ? $style = $field['style'] : $style = '';
					isset($field['standby']) ? $standby = $field['standby'] : $standby = '';
					isset($field['value']) ? $value = $field['value'] : $value = '';
					$input = renderinput($key, $field['type'], $standby, $value, $style);
					$tr = "<tr><td width='100' valign='top'>$name</td><td>$input";
					if(isset($field['ext'])) $tr .= $field['ext'];
					$tr .= "</td></tr>";
					$table .= $tr;
				}
			}
			$table .= "<tr><td colspan='2'><input type='checkbox' name='continueadd' id='continueadd' value='1' /><label for='continueadd'>{$lan['continueadd']}</label></td></tr>";
			$table .= "</table>";
			if(akgetcookie('continueadd')) $table .= "<script>$(document).ready(function(){\$('#continueadd').attr('checked', true);});</script>";
			displaytemplate('admincp_managetable_add.htm', array("table" => $table, 'formaction' => $currenturl));
		} else {
			$value = $_POST;
			if(isset($config['addsavefunction'])) {
				$addsavefunction = $config['addsavefunction'];
				if(function_exists($addsavefunction)) {
					$value = $addsavefunction($value);
					if(is_string($value)) {
						adminmsg($value, $currenturl.'&do=manage', 3, 1);
					}
				}
			}
			foreach($value as $_k => $_v) {
				if(!in_array($_k, $config['savefields'])) unset($value[$_k]);
				if(is_array($_v)) $value[$_k] = implode(',', $_v);
			}
			if(empty($value)) aexit('no field.');
			$db->insert($table, $value);
			if(isset($config['addsavedfunction'])) {
				$addsavedfunction = $config['addsavedfunction'];
				if(function_exists($addsavedfunction)) {
					$value = $addsavedfunction($value);
					if(is_string($value)) {
						adminmsg($value, $currenturl.'&do=manage', 3, 1);
					}
				}
			}
			if(empty($_POST['continueadd'])) {
				aksetcookie('continueadd', 0);
			} else {
				aksetcookie('continueadd', 1);
				aklocation($currenturl);
			}
			adminmsg($lan['operatesuccess'], $currenturl.'&do=manage');
		}
	} elseif($do == 'edit') {
		if(empty($_POST)) {
			$row = $db->get_by('*', $table, "`$primary`='".$db->addslashes($_GET[$primary])."'");
			if(isset($config['editfunction'])) {
				$editfunction = $config['editfunction'];
				if(function_exists($editfunction)) $row = $editfunction($row);
			}
			$table = '<table width="500" border="0" align="center" cellpadding="4" cellspacing="1" class="commontable"><tr class="header"><td colspan="2"><div class="righttop"><a href="'.$currenturl.'&do=manage">'.$lan['manage'].$config['name'].'&nbsp;&gt;&gt;</a></div>'.$lan['edit'].$config['name'].'</td></tr>';
			if(isset($_GET['ext']) && isset($config['extedits'][$_GET['ext']])) {
				$editfields = $config['extedits'][$_GET['ext']]['fields'];
			} else {
				$editfields = $config['addfields'];
				if(!empty($config['editfields'])) $editfields = $config['editfields'];
			}
			if(!empty($editfields)) {
				foreach($editfields as $key => $field) {
					$name = isset($field['name']) ? $field['name'] : $key;
					isset($field['style']) ? $style = $field['style'] : $style = '';
					isset($field['standby']) ? $standby = $field['standby'] : $standby = '';
					$input = renderinput($key, $field['type'], $standby, $row[$key], $style);
					$tr = "<tr><td width='100' valign='top'>$name</td><td>$input";
					if(isset($field['ext'])) $tr .= $field['ext'];
					$tr .= "</td></tr>";
					$table .= $tr;
				}
			}
			$table .= "</table>";
			displaytemplate('admincp_managetable_edit.htm', array("table" => $table, 'formaction' => $currenturl));
		} else {
			$value = $_POST;
			$row = $db->get_by('*', $table, "`$primary`='".$db->addslashes($_GET[$primary])."'");
			
			foreach($value as $_k => $_v) {
				if(isset($config['savefields']) && !in_array($_k, $config['savefields'])) unset($value[$_k]);
				if(is_array($_v)) $value[$_k] = implode(',', $_v);
			}
			
			if(isset($_GET['ext']) && isset($config['extedits'][$_GET['ext']])) {
				if(isset($config['extedits'][$_GET['ext']]['savefunction'])) $savefunction = $config['extedits'][$_GET['ext']]['savefunction'];
			} else {
				if(isset($config['editsavefunction'])) $savefunction = $config['editsavefunction'];
			}
			if(isset($savefunction) && function_exists($savefunction)) {
				$value = $savefunction($row, $value);
				if(is_string($value)) {
					adminmsg($value, $currenturl.'&do=manage', 3, 1);
				}
			}
			$db->update($table, $value, "`$primary`='".$db->addslashes($_GET[$primary])."'");
			adminmsg($lan['operatesuccess'], $currenturl.'&do=manage');
		}
	} elseif($do == 'delete') {
		vc();
		$db->delete($table, "`$primary`='".$db->addslashes($_GET[$primary])."'");
		adminmsg($lan['operatesuccess'], 'back');
	}
}

function inputshow($settings, $variable) {
	global $lan, $db;
	$output = '';
	foreach($variable as $v) {
		if(!isset($settings[$v])) {
			$db->insert('settings', array('variable' => $v, 'value' => ''));
			$settings[$v]['value'] = '';
		}
		$_f = explode('|', $lan['s_'.$v]);
		list($title, $description, $standby) = $_f;
		$type = 'string';
		if(isset($_f[3])) $type = $_f[3];
		$setting = $settings[$v];
		$input = renderinput($v, $type, $standby, $setting['value']);
		$output .= "<tr><td valign='top'><b>{$title}</b><br>{$description}</td><td valign=\"top\" width=\"300\">{$input}</td></tr>\n";
	}
	return $output;
}

function checkcategorypath($path, $up = 0) {
	global $lan, $system_root, $db, $tablepre;
	if(!empty($path)) {
		if(!preg_match('/^[_0-9a-zA-Z\-_]*$/i', $path)) return $lan['pathspecialcharacter'];
		if($db->get_by('id', 'categories', "categoryup='$up' AND path='$path'")) return $lan['categorypathused'];
	}
	return '';
}

function multi($count, $perpage, $page, $url) {
	global $lan;
	$num = ceil($count / $perpage);
	$str_index = '<ul class="index">';
	$page > 4 ? $start = $page - 4 : $start = 1;
	$num - $page > 4 ? $end = $page + 4 : $end = $num;
	for($i = $start; $i <= $end; $i ++) {
		if($i == $page) {
			$str_index .= "<li class='page'><a href={$url}&page={$i}>{$i}</a></li>";
			continue;
		}
		$str_index .= "<li><a href={$url}&page={$i}>{$i}</a></li>";
	}
	$str_index .= '</ul><div style="clear:both;">'.$lan['total'].$count.'&nbsp;/&nbsp;'.$lan['pagenum'].$num.'&nbsp;<input type="text" size="3" name="page"></div>';
	return $str_index;
}

function runinfo($message = '') {
	global $db, $ifdebug, $sysname, $sysedition, $mtime, $systemurl;
	$str_debug = $message;
	$endmtime = explode(' ', microtime());
	$exetime = number_format($endmtime[1] + $endmtime[0] - $mtime[1] - $mtime[0], 3);
	if(isset($db)) {
		if(empty($ifdebug)) {
			$str_debug .= "<div style='margin-top:10px;text-align:center;' class='mininum'>".$db->querynum.'&nbsp;queries&nbsp;Time:'.$exetime.'</div>';
		} else {
			$str_debug .= "<center><div style='margin-top: 10px;' class='mininum' onclick='show_query_debug()'>".$db->querynum.'&nbsp;queries&nbsp;Time:'.$exetime;
			if($memused = ak_memused()) {
				$str_debug .= '&nbsp;Mem:'.$memused;
				unset($memused);
			}
			$str_debug .= "</div><div style='display: none;margin-top: 10px;' id='query_debug'>\n";
			$str_debug .= "<span>".count($db->queries)." queries:</span>";
			foreach($db->queries as $query) {
				$str_debug .= "<li>".ak_htmlspecialchars($query)."</li>\n";
			}
			$str_debug .= "</div></center>\n";
			$js = "<script language='javascript'>\n";
			$js .= "function show_query_debug() {\n";
			$js .= "$('#query_debug').toggle();\n";
			$js .= "}\n";
			$js .= "</script>\n";
			$str_debug .= $js;
		}
	}
	$str_debug = ak_replace("</body>", "$str_debug\n".getcopyrightinfo()."\n</body>", ob_get_contents());
	ob_end_clean();
	echo($str_debug);
}

function createfore($foretype = '') {
	global $system_root, $settings;
	$config_data = "<?php\n$"."system_root = '{$system_root}';\n$"."foreload = 1;\n?>";
	writetofile($config_data, FORE_ROOT.'akcms_config.php');
	$files = array('attachment', 'category', 'inc', 'include', 'comment', 'item', 'page', 'rounter', 'score', 'section', 'app');
	foreach($files as $file) {
		if(!empty($foretype) && $foretype != $file) continue;
		$content = "<?php include 'akcms_config.php';\$file = '{$file}';include \$system_root.'/fore.php';?>";
		writetofile($content, FORE_ROOT.'akcms_'.$file.'.php');
	}
}

function removefore($foretype = '') {
	$foretypes = array('attachment', 'category', 'inc', 'include', 'comment', 'item', 'page', 'rounter', 'score', 'section', 'app', 'keyword');
	foreach($foretypes as $f) {
		if(empty($foretype)) @akunlink(FORE_ROOT.'akcms_'.$f.'.php');
		if(!empty($foretype) && $foretype == $f) @akunlink(FORE_ROOT.'akcms_'.$f.'.php');
	}
}

function getsysteminfos($variable) {
	global $infos;
	$infos = getcache('infos');
	return $infos[$variable];
}

function updateitemfilename($ids) {
	global $db, $tablepre;
	$_ids = implode(',', $ids);
	$query = $db->query("SELECT * FROM {$tablepre}_items WHERE id IN ($_ids)");
	$ccs = array();
	while($item = $db->fetch_array($query)) {
		if(isset($ccs[$item['category']])) {
			$cc = $ccs[$item['category']];
		} else {
			$cc = getcategorycache($item['category']);
			$ccs[$item['category']] = $cc;
		}
		$filename = itemhtmlname($item['id'], 1, $item, $cc);
		$sql = "UPDATE {$tablepre}_filenames SET filename='$filename' WHERE id={$item['id']}";
		$db->query($sql);
	}
}

function deletecommentbyip($ip) {
	global $db, $tablepre;
	$sql = "DELETE FROM {$tablepre}_comments WHERE ip='$ip'";
	$db->query($sql);
}

function extfieldinput($field) {
	$type = $field['type'];
	$standby = ak_htmlspecialchars($field['standby']);
	if($type == 'string' || $type == 'number') {
		return "<input type='text' name='ext_{$field['alias']}' id='ext_{$field['alias']}' value='{$field['standby']}' size='60'>";
	} elseif($type == 'select') {
		$return = '';
		$items = explode("\n", $standby);
		foreach($items as $item) {
			$f = explode(',', trim($item));
			$v = $t = $f[0];
			if(isset($f[1])) $t = $f[1];
			$return .= "<option value=\"{$v}\">{$t}</option>\n";
		}
		return "<select name=\"ext_{$field['alias']}\">{$return}</select>";
	} elseif($type == 'radio') {
		$return = '';
		$items = explode("\n", $standby);
		$i = 0;
		foreach($items as $item) {
			$i ++;
			$f = explode(',', trim($item));
			$v = $t = $f[0];
			if(isset($f[1])) $t = $f[1];
			$id = "{$field['alias']}_{$i}";
			$return .= "<input type=\"radio\" id=\"$id\" name=\"ext_{$field['alias']}\" value=\"{$v}\"><label for=\"{$id}\">{$t}</label>\n";
		}
		return $return;
	}
}

function rendermodulefield($key, $data, $value = false) {
	global $lan;
	$alias = fieldname($key, $data);
	if($key == 'dateline' && !empty($value)) $value = date('Y-m-d H:i:s', $value);
	$htmlfields = "<tr><td width='60' valign='top'>{$alias}</td>";
	if(!empty($data['size'])) {
		if(strpos($data['size'], ',') === false) {
			$width = $data['size'];
		} else {
			list($width, $height) = explode(',', $data['size']);
		}
	}
	if($key == 'data' || $key == 'digest' || strpos($key, 'string') === 0 || (isset($data['type']) && $data['type'] == 'rich')) {
		if(empty($width)) $width = '100%';
		if(empty($height)) $height = '400';
	}
	if(!empty($height) && $key != 'paging') {
		if(substr($width, -1) != '%') $width .= 'px';
		if(substr($height, -1) != '%') $height .= 'px';
		if($data['type'] == 'plain') {
			$value = br2nl($value);
			$htmlfields .= "<td><textarea name='{$key}' style='width:{$width};height:{$height};'>".ak_htmlspecialchars($value)."</textarea>";
		} elseif($data['type'] == 'rich') {
			$htmlfields .= "<td>".editor($key, 'rich', $value, array('uploadimgurl' => 'index.php?file=upload&id=[itemid]'));
			$htmlfields .= "<input type='checkbox' value='1' name='{$key}_copypic' id='{$key}_copypic'><label for='{$key}_copypic'>".$lan['copypicturetolocal'].'</label>';
			$htmlfields .= "<br /><input type='checkbox' value='1' name='{$key}_pickpicture' id='{$key}_pickpicture'><label for='{$key}_pickpicture'>".$lan['pickpicture'].'</label>';
		} else {
			$htmlfields .= "<td><input type=\"text\" name=\"$key\" value=\"".ak_htmlspecialchars($value)."\" style='width:{$width}'>";
		}
	} elseif($key == 'picture') {
		$htmlfields .= "<td><table><tr><td>{$lan['pictureurl']}:<input type='text' name='picture' value='{$value}' size='50'>";
		$picture = pictureurl($value);
		if($picture != '') $htmlfields .= "<a href='$picture' target='_blank'>{$lan['preview']}</a>";
		$htmlfields .= "</td></tr><tr><td>{$lan['or']}</td></tr><tr><td>{$lan['uploadpicture']}:<input type='file' name='uploadpicture' value=''></td></tr></table>";
	} elseif($key == 'attach') {
		$htmlfields .= "<td>";
		$htmlfields .= "{$lan['upload']}&nbsp;:&nbsp;<input style='width:130px;' id='multiple' type='file' multiple /><span id='upinfo'></span>";
		$htmlfields .= "<div id='attachments'></div></td>";
	} elseif($key == 'paging') {
		$htmlfields .= "<td><div id='paging'></div>";
	} elseif($key == 'title') {
		$width = empty($data['size']) ? '240' : $data['size'];
		if(substr($width, -1) != '%') $width .= 'px';
		$value = ak_htmlspecialchars($value);
		$htmlfields .= "<td><input type='text' id='title' name='$key' value=\"{$value}\" style='width:{$width}' class='mustoffer'>";
		if(!empty($data['iftitlestyle'])) {
			$htmlfields .= "&nbsp;<select id='titlecolor' name='titlecolor'><option value=''>{$lan['color']}</option>";
			for($i = 0; $i < 3; $i ++) {
				for($j = 0; $j < 3; $j ++) {
					for($k = 0; $k < 3; $k ++) {
						$c = (string)$i.(string)$j.(string)$k;
						$c = str_replace('0', '00', $c);
						$c = str_replace('1', '80', $c);
						$c = str_replace('2', 'FF', $c);
						$htmlfields .= "<option value='$c' style='background-color:$c'>&nbsp;</option>";
					}
				}
			}
			$htmlfields .= "</select>&nbsp;<select id='titlestyle' name='titlestyle'><option value=''>{$lan['style']}</option><option value='b'>{$lan['bold']}</option><option value='i'>{$lan['italic']}</option></select>";
		}
		if(!empty($value)) $htmlfields .= "&nbsp;<input type='button' style='background:red;color:#FFF' value='{$lan['delete']}' onclick='return confirmdelete();'>";
	} else {
		if(strpos($data['default'], ';') === false) {
			$width = empty($data['size']) ? '240' : $data['size'];
			if(substr($width, -1) != '%') $width .= 'px';
			if($value === false) $value = $data['default'];
			$htmlfields .= "<td><input type=\"text\" name=\"$key\" value=\"".ak_htmlspecialchars($value)."\" style='width:{$width}'>";
		} else {
			$options = explode(';', $data['default']);
			$optionshtml = '';
			foreach($options as $_k => $option) {
				if(strpos($option, ',') === false) {
					$optionshtml .= "<option value=\"$option\">$option</option>";
				} elseif(substr_count($option, ',') == 1) {
					list($t, $v) = explode(',', $option);
					$optionshtml .= "<option value=\"$v\">$t</option>";
				}
			}
			$htmlfields .= "<td><select id=\"$key\" name=\"$key\">{$optionshtml}</select>";
			if(!empty($value)) $htmlfields .= "<script>$('#{$key}').val(\"".ak_htmlspecialchars($value)."\");</script>";
		}
	}
	if(!empty($data['description'])) $htmlfields .= ' '.$data['description'];
	$htmlfields .= "</td></tr>";
	return $htmlfields;
}

function getcategoriesbymodule($module){
	global $db;
	if($module == 0 || $module == -1) {
		$query = $db->query_by('id', 'categories', "module>0");
	} else {
		$query = $db->query_by('id', 'categories', "module='$module'");
	}
	$return = array();
	while($category = $db->fetch_array($query)) {
		$return[] = $category['id'];
	}
	return $return;
}

function ifitemtemplateexist($category, $template = '') {
	global $template_path;
	$categorycache = getcategorycache($category);
	if(empty($template)) $template = $categorycache['itemtemplate'];
 	$templatefile = AK_ROOT."configs/templates/$template_path/,".$template;
	if(!file_exists($templatefile)) return false;
	return true;
}

function table_start($title = '', $colspan = 10) {
	global $lan;
	return "<table cellpadding=\"4\" cellspacing=\"1\" class=\"commontable width100\"><tr class=\"header\">\n".
	"<td colspan=\"{$colspan}\"><div class=\"righttop\">".h('setting:help')."</div>{$title}</td>\n".
	"</tr>";
}

function table_end() {
	return "</table>\n<div class=\"block2\"></div>";
}

function table_next($title = '', $colspan = 10) {
	$output = table_end();
	$output .= table_start($title, $colspan);
	return $output;
}

function modulefields($data = array()) {
	global $itemfields, $lan, $settings;
	$fieldshtml = '';
	$trid = 0;
	foreach($itemfields as $field) {
		if(a_is_int(substr($field, -1))) {
			$_k = substr($field, 0, -1);
			$l = str_replace($_k, $lan[$_k], $field);
		} elseif($field == 'dateline') {
			$l = $lan['time'];
		} else {
			$l = isset($lan[$field]) ? $lan[$field] : $field;
		}
		$setting = '';
		if($field == 'data' || $field == 'digest' || $field == 'paging' || strpos($field, 'string') === 0) {
			$setting = "<select name='{$field}_type' id='{$field}_type'>".returnmodulefieldtype()."</select>";
			if(!empty($data[$field]['type'])) $setting .= "<script>$('#{$field}_type option[value={$data[$field]['type']}]').attr('selected',true);</script>";
		}
		if($field == 'title') {
			$setting .= "<input type='checkbox' name='iftitlestyle' id='iftitlestyle' value='1'><label for='iftitlestyle'>{$lan['style']}</label>";
			if(!empty($data['title']['iftitlestyle'])) $setting .= "<script>$('#iftitlestyle').attr('checked', true); </script>";
		}
		$_alias = isset($data[$field]['alias']) ? $data[$field]['alias'] : '';
		$_order = isset($data[$field]['order']) ? $data[$field]['order'] : '';
		$_listorder = isset($data[$field]['listorder']) ? $data[$field]['listorder'] : '';
		$_description = isset($data[$field]['description']) ? $data[$field]['description'] : '';
		$_default = isset($data[$field]['default']) ? $data[$field]['default'] : '';
		$_size = isset($data[$field]['size']) ? $data[$field]['size'] : '';
		if(!in_array($field, array('filename', 'comment', 'paging', 'template'))) {
			$fieldtip="<td>\${$field}</td><td>[{$field}]</td>";
		} else {
			$fieldtip="<td>-</td><td>-</td>";
		}
		$fieldshtml .= "
<tr>
<td>{$l}</td>{$fieldtip}
<td><input type='text' name='{$field}_alias' size='10' value='{$_alias}'></td>
<td><input type='text' name='{$field}_order' size='3' value='{$_order}'></td>
<td><input type='text' name='{$field}_listorder' size='3' value='{$_listorder}'></td>
<td><input type='text' name='{$field}_description' size='16' value='{$_description}'></td>
<td><input type='text' name='{$field}_default' size='10' value='{$_default}'></td>";
		$trid ++;
		if($field == 'category' || $field == 'section' || $field == 'attach' || $field == 'picture' || $field == 'comment') {
			$fieldshtml .= "<td>-</td><td>-</td></tr>\n";
		} else {
			$fieldshtml .= "<td><input type='text' name='{$field}_size' size='9' value='{$_size}'></td><td>{$setting}</td></tr>\n";
		}
	}
	$trid += 4;
	$fieldshtml .= "<tr class='header'><td colspan='10'>{$lan['extfields']}&nbsp;(<a href=\"javascript:addfield()\">+{$lan['add']}</a>)</td></tr>";
	$fieldshtml .= "<tr><td>{$lan['field']}({$lan['letterornum']})</td><td>{$lan['variable']} <a target='_blank' class='help' href='http://www.akhtm.com/manual/template-variables.htm?source=cp' title='{$lan['help']}'>?</a></td><td>{$lan['tag']} <a target='_blank' class='help' href='http://www.akhtm.com/manual/getitems.htm?source=cp' title='{$lan['help']}'>?</a></td><td>{$lan['alias']}</td><td>{$lan['order']}</td><td>{$lan['listorder']}</td><td>{$lan['description']}</td><td>{$lan['default']}</td><td>{$lan['fieldsize']}</td><td>{$lan['type']}</td></tr>";
	foreach($data as $_k => $_v) {
		if(substr($_k, 0, 1) != '_') continue;
		$k = substr($_k, 1);
		$field = $_k;
		$setting = "<select id='extfield_type{$trid}' name='extfield_type{$trid}'>".returnmodulefieldtype()."</select>";
		if($data[$_k]['type']) $setting .= "<script>$('#extfield_type{$trid}').val('{$data[$_k]['type']}');</script>";
		$_data = isset($data[$_k]) ? $data[$_k] : '';
		$_alias = isset($data[$field]['alias']) ? $data[$field]['alias'] : '';
		$_order = isset($_data['order']) ? $_data['order'] : '';
		$_listorder = isset($_data['listorder']) ? $_data['listorder'] : '';
		$_description = isset($_data['description']) ? $_data['description'] : '';
		$_default = isset($_data['default']) ? $_data['default'] : '';
		$_size = isset($_data['size']) ? $_data['size'] : '';
		$fieldshtml .= "<tr><td><input name='extfield{$trid}' type='text' size='12' value='".ak_htmlspecialchars($k)."'></td><td>\${$_k}</td><td>[{$_k}]</td><td><input type='text' name='extfield_alias{$trid}' size='10' value='".ak_htmlspecialchars($_alias)."'></td><td><input type='text' name='extfield_order{$trid}' size='3' value='".htmlspecialchars($_order)."'></td>
	<td><input type='text' name='extfield_listorder{$trid}' size='3' value='".ak_htmlspecialchars($_listorder)."'></td>
	<td><input type='text' name='extfield_description{$trid}' size='15' value='".ak_htmlspecialchars($_description)."'></td>
	<td><input type='text' name='extfield_default{$trid}' size='10' value='".ak_htmlspecialchars($_default)."'></td><td><input type='text' name='extfield_size{$trid}' size='9' value='{$_size}'></td><td>{$setting}</td></tr>";
		$trid ++;
	}
	return $fieldshtml;
}

function renderitemfield($moduleid, $itemvalue = array()) {
	global $db, $lan;
	$modules = getcache('modules');
	$data = $modules[$moduleid]['data'];
	$fields = array();
	if(empty($modules[$moduleid]['categories'])) return false;
	foreach($data['fields'] as $key => $value) {
		$fields[$key] = $value['order'];
	}
	arsort($fields);
	$categoryalias = $lan['category'];
	if(!empty($modules[$moduleid]['data']['fields']['category']['alias'])) $categoryalias = $modules[$moduleid]['data']['fields']['category']['alias'];
	empty($categoryalias) && $categoryalias = $lan['category'];
	if($data['fields']['category']['order'] <= 0) {
		$_category = each($modules[$moduleid]['categories']);
		$categoryfield = "<input type='hidden' name='category' value='{$_category[0]}'>";
	} elseif(empty($GLOBALS['nocategoryselect'])) {
		$categoryfield = "<tr><td>{$categoryalias}</td>".'<td class="categoryselect"><select id="category0" onchange="javascript:selectcategory(1, this.value)"></select><select id="category1" onchange="javascript:selectcategory(2, this.value)"></select><select id="category2" onchange="javascript:selectcategory(3, this.value)"></select><select id="category3" onchange="javascript:selectcategory(4, this.value)"></select><select id="category4" onchange="javascript:selectcategory(5, this.value)"></select><select id="category5" onchange="javascript:selectcategory(6, this.value)"></select><select id="category6" onchange="javascript:selectcategory(7, this.value)"></select><select id="category7" onchange="javascript:selectcategory(8, this.value)"></select><select id="category8" onchange="javascript:selectcategory(9, this.value)"></select><select id="category9" onchange="javascript:selectcategory(10, this.value)"></select><input type="hidden" id="category" name="category"><script language="javascript">$(document).ready(function(){selectcategory(0, 0);});</script></td></tr>';
	} else {
		$categoryfield = "<tr><td>{$categoryalias}</td><td><input name='category' size='6' id='category' class='mustoffer' /></td></tr>";
	}
	$htmlfields = '';
	foreach($fields as $key => $value) {
		if($data['fields'][$key]['order'] <= 0 && $key != 'category') continue;
		$itemfieldvalue = false;
		if(isset($itemvalue[$key])) $itemfieldvalue = $itemvalue[$key];
		if($key == 'category') {
			$htmlfields .= $categoryfield;
			if(isset($itemvalue['category'])) $htmlfields .= '<script>$(document).ready(function(){$("#category").val('.$itemvalue['category'].');});</script>';
		} elseif($key == 'section') {
			$sectionalias = $modules[$moduleid]['data']['fields']['section']['alias'];
			empty($sectionalias) && $sectionalias = $lan['section'];
			$htmlfields .= "<tr><td>{$sectionalias}</td><td><select name='section' id='section'>".get_select('section')."</select></td></tr>";
			if(isset($itemvalue['section'])) $htmlfields .= '<script>$(document).ready(function(){$("#section").val('.$itemvalue['section'].');});</script>';
		} elseif($key == 'template') {
			$templatealias = $modules[$moduleid]['data']['fields']['template']['alias'];
			empty($templatealias) && $templatealias = $lan['template'];
			$htmlfields .= "<tr><td>{$templatealias}</td><td><select name='template' id='template'><option value=''>{$lan['default']}</option>".get_select_templates()."</select></td></tr>";
			if(isset($itemvalue['template'])) $htmlfields .= '<script>$(document).ready(function(){$("#template").val("'.$itemvalue['template'].'");});</script>';
		} elseif($key == 'comment') {
			$commentalias = $modules[$moduleid]['data']['fields']['comment']['alias'];
			empty($commentalias) && $commentalias = $lan['comment'];
			$htmlfields .= "<tr><td>{$commentalias}</td><td align='left'><div id='comments'></div></td></tr>";
		} else {
			if($key == 'paging' || $key == 'attach') $itemfieldvalue = $itemvalue['id'];
			$modulefield = rendermodulefield($key, $data['fields'][$key], $itemfieldvalue);
			if($key == 'title') {
				if(!empty($itemvalue['titlestyle'])) $modulefield .= "<script>$('#titlestyle').val('{$itemvalue['titlestyle']}');</script>";
				if(!empty($itemvalue['titlecolor'])) $modulefield .= "<script>$('#titlecolor').val('{$itemvalue['titlecolor']}');</script>";
			}
			if(isset($itemvalue['id'])) $modulefield = ak_replace('id=[itemid]', "id={$itemvalue['id']}", $modulefield);
			$htmlfields .= $modulefield;
		}
	}
	$htmlfields .= "<input type='hidden' name='uploadid' value='{$itemvalue['id']}' />";
	return $htmlfields;
}

function batchdeleteitem($array_id) {
	global $db, $tablepre;
	$ids = implode(',', $array_id);
	$db->delete('texts', "itemid IN ({$ids})");
	$query = $db->query_by('*', 'attachments', "itemid IN ({$ids})");
	while($attach = $db->fetch_array($query)) {
		@akunlink(FORE_ROOT.$attach['filename']);
	}
	$db->delete('attachments', "itemid IN ({$ids})");
	$db->delete('filenames', "id IN ({$ids}) AND type='item'");
	$array_sections = array();
	$array_categories = array();
	$array_editors = array();
	$query = $db->query_by('*', 'items', "id IN ($ids)");
	while($item = $db->fetch_array($query)) {
		$array_sections[] = $item['section'];
		$array_categories[] = $item['category'];
		$array_editors[] = $item['editor'];
		@akunlink(FORE_ROOT.itemhtmlname($item['id'], 1, $item));
		if(!empty($item['picture'])) @akunlink(FORE_ROOT.$item['picture']);
	}
	$db->delete('items', "id IN ({$ids})");
	$db->delete('item_exts', "id IN ({$ids})");
	refreshitemnum($array_categories, 'category');
	refreshitemnum($array_sections, 'section');
	refreshitemnum($array_editors, 'editor');
}

function showprocess($title, $processurl, $targeturl = '', $timeout = 100, $completetipmsg = '', $steps = array()) {
	global $header_charset, $lan;
	if(empty($completetipmsg)) $completetipmsg = $lan['operatesuccess'];
	displaytemplate('admincp_process.htm', array('title' => $title, 'processurl' => $processurl, 'targeturl' => $targeturl, 'timeout' => $timeout, 'completetipmsg' => $completetipmsg, 'steps' => $steps));
	runinfo();
	aexit();
}

function returnmodulefieldtype() {
	global $lan;
	return "<option value='string'>{$lan['string']}</option><option value='rich'>{$lan['richtext']}</option><option value='plain'>{$lan['plaintext']}</option>";
}

function freezeuser($uid, $freeze = 1) {
	global $db;
	$db->update('users', array('freeze' => $freeze), "id='$uid'");
}

function getmenus($id = 'system') {
	global $lan, $homepage;
	$config = CORE_ROOT.'include/menu.xml';
	if($id == 'custom') $config = AK_ROOT.'configs/custom.menu.xml';
	$xml = readfromfile($config);
	$xml = str_replace('&', '&amp;', $xml);
	$p = xml_parser_create();
	xml_parse_into_struct($p, $xml, $values, $index);
	$groups = array();
	foreach($values as $v) {
		if($v['tag'] == 'GROUP' AND $v['type'] == 'open') {
			$gid = $v['attributes']['ID'];
			$groups[$gid] = array(
				'title' => getxmltext($v['attributes']['TEXT']),
				'menus' => array()
			);
		}
		if($v['tag'] == 'MENU') {
			$url = $v['attributes']['URL'];
			$url = str_replace('[homepage]', $homepage, $url);
			$id = '';
			if(isset($v['attributes']['ID'])) $id = $v['attributes']['ID'];
			$text = getxmltext($v['value']);
			$menu = array('url' => $url, 'text' => $text, 'id' => $id);
			if(isset($v['attributes']['PLUSURL'])) $menu['plus'] = $v['attributes']['PLUSURL'];
			$groups[$gid]['menus'][] = $menu;
		}
		if($v['tag'] == 'MODULE') {
			$modules = getcache('modules');
			foreach($modules as $module) {
				if($module['id'] <= 0) continue;
				$menu = array('url' => "index.php?file=admincp&action=items&module={$module['id']}", 'text' => $lan['manage'].$lan['space'].$module['modulename'], 'plus' => "index.php?file=admincp&action=newitem&module={$module['id']}");
				$groups[$gid]['menus'][] = $menu;
			}
		}
		if($v['tag'] == 'HOMEPAGE') {
			$homepage = $v['value'];
		}
	}
	if(file_exists(AK_ROOT.'configs/appmenu.php')) {
		if($fp = fopen(AK_ROOT.'configs/appmenu.php', 'r')) {
			while(!feof($fp)) {
				$line = trim(fgets($fp, 1024));
				if(preg_match("/^<nav id=([0-9a-zA-Z]+)>(.+?)<\/nav>$/i", $line, $match)) {
					$groups[$match[1]]['title'] = $match[2];
					$groups[$match[1]]['menus'] = array();
					continue;
				}
				if(preg_match("/^<a href=(.+?) nav=([0-9a-zA-Z]+)>(.+?)<\/a>/i", $line, $match)) {
					$menu = array('url' => $match[1], 'text' => $match[3]);
					$groups[$match[2]]['menus'][] = $menu;
					continue;
				}
			}
		}
	}
	$menus = array(
		'groups' => $groups,
		'homepage' => $homepage
	);
	return $menus;
}

function rendermenu($groups) {
	global $setting_menuwidth;
	$html = '';
	$menuwidth = $setting_menuwidth;
	foreach($groups as $k => $group) {
		$html .= "<div id='menu_{$k}' class='menu_body'><div class='menutitle'>{$group['title']}</div><ul>";
		foreach($group['menus'] as $menu) {
			$menuhtml = rendermenulink($menu);
			if(!empty($menu['id'])) {
				$html .= "<li id='{$menu['id']}'>$menuhtml</li>";
			} else {
				$html .= "<li>$menuhtml</li>";
			}
		}
		$html .= "</ul></div>";
	}
	return $html;
}

function rendermenulink($menu) {
	global $vc;
	if($menu['url'] == 'index.php?file=admincp&action=custom#') {
		if(!file_exists(AK_ROOT.'configs/custom.menu.xml')) return '';
	}
	$plus = isset($menu['plus']) ? "<a href='{$menu['plus']}' class='menuplus'>+</a>" : '';
	$flag = substr($menu['url'], -1);
	$target = '';
	if(in_array($flag, array('*', '#'))) {
		if($flag == '*') {
			$target = ' target="_blank"';
		} else {
			$target = ' target="_self"';
		}
		$menu['url'] = substr($menu['url'], 0, -1);
	}
	$menu['url'] = str_replace('[vc]', $vc, $menu['url']);
	return "{$plus}<a href='{$menu['url']}'{$target}>{$menu['text']}</a>";
}

function rendernav($groups) {
	$html = '<ul>';
	foreach($groups as $k => $group) {
		$html .= "<li id='$k'>{$group['title']}</li>";
	}
	$html .= '</ul>';
	return $html;
}

function getxmltext($text) {
	global $lan;
	$text = fromutf8($text);
	if(substr($text, 0, 4) == 'lan.') $text = $lan[substr($text, 4)];
	return $text;
}

function editor($name, $type = 'text', $value = '', $ext = array()) {
	$width = empty($ext['width']) ? '100%' : $ext['width'];
	$height = empty($ext['height']) ? '399px' : $ext['height'];
	$uploadimg = empty($ext['uploadimgurl']) ? '' : "upImgUrl:'{$ext['uploadimgurl']}',";
	$id = empty($ext['id']) ? $name.'_'.random(6) : $ext['id'];
	$class = '';
	if($type == 'rich') $class = " class=\"xheditor {hoverExecDelay:-1,{$uploadimg}upImgExt:'jpg,jpeg,gif,png',tools:'Source,Pastetext,|,Blocktag,Fontface,FontSize,Bold,Italic,Underline,Strikethrough,FontColor,BackColor,SelectAll,Removeformat,|,Align,List,Outdent,Indent,|,Link,Unlink,Anchor,Img,Hr,Table',loadCSS:'<style>body{font-size:14px;}</style>'}\"";
	$value = ak_htmlspecialchars($value);
	return "<textarea id='$id' name='$name' style='width:$width;height:$height;'$class>".$value."</textarea>";
}

function h($params) {
	global $lan;
	$k = $params;
	if(is_array($params)) $k = $params['k'];
	if(strpos($k, ':') === false) {
		$manual = $k;
		$text = $lan['help'];
	} else {
		list($manual, $text) = explode(':', $k);
		if($text != '?') $text = $lan[$text];
	}
	$class = '';
	if(isset($params['class'])) $class = $params['class'];
	return "<a href='http://www.akhtm.com/manual/{$manual}.htm?source=cp' class='$class' target='_blank'>{$text}</a>";
}

function checkuploadfile($content) {
	if(strpos($content, '<?') !== false && preg_match('/eval\(/i', $content)) return false;
	return true;
}

function savevariables($variables) {
	global $db;
	$variables = explode(',', $variables);
	foreach($variables as $variable) {
		if(!isset($_POST[$variable])) continue;
		$value = $_POST[$variable];
		if(is_array($value)) $value = implode(',', $value);
		$db->update('variables', array('value' => $value), "variable='$variable'");
	}
	require_once(CORE_ROOT.'include/cache.func.php');
	updatecache('globalvariables');
}

function fieldname($key, $setting) {
	global $lan;
	if(!empty($setting['alias'])) return $setting['alias'];
	if(isset($lan[$key])) return $lan[$key];
	if($key == 'dateline') return $lan['time'];
	if(strlen($key) == 8 && substr($key, 0, 7) == 'orderby') return $lan['order'].substr($key, 7);
	if(strlen($key) == 7 && substr($key, 0, 6) == 'string') return $lan['string'].substr($key, 6);
	if(strlen($key) == 3 && substr($key, 0, 2) == 'pv') return $lan['pv'].substr($key, 2);
}
?>

<?php
if(!defined('CORE_ROOT')) exit;
function checkcreator() {
	global $lan, $admin_id;
	if(iscreator($admin_id) != 1) adminmsg($lan['forcreatoronly'], '', 0, 1);
}

function ifallowtheme() {
	if(file_exists(AK_ROOT.'configs/theme.lock')) return false;
	return true;
}

function preparetheme() {
	@unlink(AK_ROOT.'configs/theme.lock');
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
	global $smarty;
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
	$smarty->assign('flag', $flag);
	$smarty->assign('message', $message);
	$smarty->assign('url_forward', $url_forward);
	$smarty->assign('timeout', $timeout);
	$smarty->assign('timeout_micro', $timeout * 1000);
	displaytemplate('message.htm');
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

function inputshow($settings, $variable) {
	global $lan, $db;
	$output = '';
	$cs = array();
	$cs['ifhtml']['type'] = 'radio';
	$cs['usefilename']['type'] = 'radio';
	$cs['commentneedcaptcha']['type'] = 'radio';
	$cs['forbidstat']['type'] = 'radio';
	$cs['ifuser']['type'] = 'radio';
	$cs['ifcomment']['type'] = 'radio';
	$cs['ifguestcomment']['type'] = 'radio';
	$cs['ifcommentrehtml']['type'] = 'radio';
	$cs['language']['type'] = 'radio';
	$cs['attachimagequality']['type'] = 'radio';
	$cs['attachwatermarkposition']['type'] = 'radio';
	$cs['ifdraft']['type'] = 'radio';
	$cs['cdn']['type'] = 'string';
	$cs['cdnid']['type'] = 'string';
	$cs['cdnsecret']['type'] = 'string';
	$cs['cdnpath']['type'] = 'string';
	$cs['maxattachsize']['type'] = 'int';
	$cs['template2']['type'] = 'template';
	$cs['template3']['type'] = 'template';
	$cs['template4']['type'] = 'template';
	$cs['pagetemplate']['type'] = 'template';
	$cs['tksecrets']['type'] = 'text';
	foreach($variable as $v) {
		if(!isset($settings[$v])) {
			$db->insert('settings', array('variable' => $v, 'value' => ''));
			$settings[$v]['value'] = '';
		}
		list($title, $description, $standby) = explode('|', $lan['s_'.$v]);
		if(empty($cs[$v]['type'])) $cs[$v]['type'] = 'string';
		$setting = $settings[$v];
		$input = renderinput($v, $cs[$v]['type'], $standby, $setting['value']);
		$output .= "<tr><td valign='top'><b>{$title}</b><br>{$description}</td><td valign=\"top\" width=\"300\">{$input}</td></tr>\n";
	}
	return $output;
}

function renderinput($name, $type = 'string', $standby = '', $value = '') {
	global $lan;
	$input = '';
	if($type == 'string') {
		$input = '<input type="text" name="'.$name.'" value="'.ak_htmlspecialchars($value).'" style="width:100%;">';
	} elseif($type == 'int') {
		$input = '<input type="text" name="'.$name.'" value="'.ak_htmlspecialchars($value).'" size="15">';
	} elseif($type == 'pass') {
		$input = '<input type="password" name="'.$name.'" value="'.$value.'" size="50">';
	} elseif($type == 'radio' || $type == 'checkbox') {
		$tag = $type;
		$options = explode(';', $standby);
		foreach($options as $option) {
			$checked = '';
			if(strpos($option, ',') === false) {
				$_text = $_value = $option;
			} else {
				list($_text, $_value) = explode(',', $option);
			}
			$key = md5($_value);
			$values = explode(',', $value);
			if(in_array($_value, $values)) $checked = ' checked';
			$input .= "<input type='$type' name='{$name}[]' id='{$name}_{$key}' value='$_value'$checked>&nbsp;<label for='{$name}_{$key}'>$_text</label>&nbsp;";
		}
	} elseif($type == 'text') {
		$input = "<textarea name='$name' style='width:500px;height:100px;'>$value</textarea>";
	} elseif($type == 'richtext') {
		$input = "<textarea name='$name' style='width:600px;height:200px;' class=\"xheditor {hoverExecDelay:-1,upImgExt:'jpg,jpeg,gif,png',tools:'Source,Pastetext,|,Blocktag,Fontface,FontSize,Bold,Italic,Underline,Strikethrough,FontColor,BackColor,SelectAll,Removeformat,|,Align,List,Outdent,Indent,|,Link,Unlink,Anchor,Img,Hr,Table',loadCSS:'<style>body{font-size:14px;}</style>'}\">$value</textarea>";
	} elseif($type == 'category') {
		$input = "<select id='$name' name='{$name}'>".get_select('category').'</select><script>$(document).ready(function(){$("#'.$name.'").val('.$value.');});</script>';
	} elseif($type == 'categories') {
		$input = "<select id='$name' name='{$name}[]' multiple>".get_select('category');
		if(strpos($value,',') === false) $value = "'".$value."'";
		$input .= "</select><script>function ifchecked{$name}(obj) {ids = new Array($value);for(i=0;i<ids.length;i++){if(ids[i]==obj.val()){obj.attr('selected','selected');}}}$('#{$name}').children().each(function(){ifchecked{$name}(\$(this));});</script>";
	} elseif($type == 'section') {
		$input = "<select id='$name' name='{$name}'>".get_select('section').'</select><script>$(document).ready(function(){$("#$name").val($value);});</script>';
	} elseif($type == 'template') {
		$input = "<select id='$name' name='{$name}'><option value=''>{$lan['pleasechoose']}</option>".get_select_templates().'</select><script>$(document).ready(function(){$("#'.$name.'").val("'.$value.'");});</script>';
	} elseif($type == 'picture') {
		$input = "<table><tr><td>{$lan['pictureurl']}:<input type='text' name='{$name}' value='{$value}' size='50'>";
		if(!empty($value)) {
			$value = pictureurl($value);
			$input .= " <a href='$value' target='_blank'>{$lan['preview']}</a>";
		}
		$input .= "</td></tr><tr><td>{$lan['or']}</td></tr><tr><td>{$lan['uploadpicture']}:<input type='file' name='{$name}_upload' value=''></td></tr></table>";
	}
	return $input."\n";
}

function checkcategorypath($path, $up = 0) {
	global $lan, $system_root, $db, $tablepre;
	if(!empty($path)) {
		if(!preg_match('/^[_0-9a-zA-Z\-_]*$/i', $path)) return $lan['pathspecialcharacter'];
		if($db->get_by('id', 'categories', "categoryup='$up' AND path='$path'")) return $lan['categorypathused'];
	}
	return '';
}

function runinfo($message = '') {
	global $db, $ifdebug, $sysname, $sysedition, $mtime, $systemurl;
	$str_debug = $message;
	$endmtime = explode(' ', microtime());
	$exetime = number_format($endmtime[1] + $endmtime[0] - $mtime[1] - $mtime[0], 3);
	if(isset($db)) {
		if(empty($ifdebug)) {
			$str_debug .= "<center><div style='margin-top: 10px;' class='mininum'>".$db->querynum.'&nbsp;queries&nbsp;Time:'.$exetime.'</div>';
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
			$js .= "var debug = document.getElementById('query_debug');\n";
			$js .= "if(debug.style.display == 'block') {\n";
			$js .= "	debug.style.display = 'none';\n";
			$js .= "} else {\n";
			$js .= "	debug.style.display = 'block';\n";
			$js .= "}\n";
			$js .= "}\n";
			$js .= "</script>\n";
			$str_debug .= $js;
		}
	}
	$str_debug = ak_replace("</body>", "$str_debug\n".getcopyrightinfo()."\n</body>", ob_get_contents());
	ob_end_clean();
	echo($str_debug);
}

function createfore() {
	global $system_root;
	$config_data = "<?php\n$"."system_root = '{$system_root}';\n$"."foreload = 1;\n?>";
	writetofile($config_data, FORE_ROOT.'akcms_config.php');
	$files = array('attachment', 'captcha', 'category', 'comment', 'inc', 'include', 'item', 'keyword', 'page', 'post', 'rounter', 'score', 'section', 'user');
	foreach($files as $file) {
		$content = "<?php include 'akcms_config.php';\$file = '{$file}';include \$system_root.'/fore.php';?>";
		writetofile($content, FORE_ROOT.'akcms_'.$file.'.php');
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
		if(isset($ccs[$item['category']])) $cc = $ccs[$item['category']];
		$filename = itemhtmlname($item['id'], 1, $item, $cc);
		$sql = "UPDATE {$tablepre}_filenames SET filename='$filename' WHERE item['id']";
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
	if(!empty($data['alias'])) {
		$alias = $data['alias'];
	} elseif(substr($key, 0, 7) == 'orderby') {
		$alias = $lan['order'].substr($key, 7);
	} elseif(isset($lan[$key])) {
		$alias = $lan[$key];
	} elseif($key == 'dateline') {
		$alias = $lan['time'];
	} else {
		if(empty($extfields)) $extfields = getcache('extfields');
		$alias = $extfields[$key]['name'];
	}
	if($key == 'dateline' && !empty($value)) $value = date('Y-m-d H:i:s', $value);
	$htmlfields = "<tr><td width='50' valign='top'>{$alias}</td>";
	if(!empty($data['size'])) {
		if(strpos($data['size'], ',') === false) {
			$width = $data['size'];
		} else {
			list($width, $height) = explode(',', $data['size']);
		}
	}
	if($key == 'data' || $key == 'digest' || (isset($data['type']) && $data['type'] == 'rich')) {
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
			$htmlfields .= "<td><textarea name='{$key}' id='{$key}' style='width:{$width};height:{$height};' class=\"xheditor {hoverExecDelay:-1,upImgUrl:'index.php?file=upload&id=[itemid]',upImgExt:'jpg,jpeg,gif,png',tools:'Source,Pastetext,|,Blocktag,Fontface,FontSize,Bold,Italic,Underline,Strikethrough,FontColor,BackColor,SelectAll,Removeformat,|,Align,List,Outdent,Indent,|,Link,Unlink,Anchor,Img,Hr,Table',loadCSS:'<style>body{font-size:14px;}</style>'}\">".ak_htmlspecialchars($value)."</textarea>";
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
		$htmlfields .= "<div style='display:none'><div id='firstattach'>{$lan['attach']}<input type='file' name='attach[]' value='' /><br />{$lan['description']}({$lan['limit255']})<br /><textarea name='description[]' cols='60' rows='3'></textarea><br /></div></div><script>function addattach() {adddiv = $('#firstattach').html();$(adddiv).appendTo('#otherattach');}</script><td>";
		if($value > 0) $htmlfields .= "<iframe id='attachments' onload='Javascript:SetframeHeight(\"attachments\")' src='index.php?file=admincp&action=attachments&id=[itemid]&r=".random(6)."' frameborder='0' style='overflow-x:hidden;overflow-y:hidden;margin:0px auto;width:100%;margin-bottom:8px;'></iframe>";
		$htmlfields .= "<div id='otherattach'></div><input type='button' value='{$lan['add']}{$lan['space']}{$lan['attach']}' onclick='addattach()'><script>addattach();</script>";
	} elseif($key == 'paging') {
		$htmlfields .= "<td><iframe id='paging' onload='Javascript:SetframeHeight(\"paging\")' src='index.php?file=admincp&action=paging&id={$value}&type={$data['type']}&r=".random(6)."' frameborder='0' style='overflow-x:hidden;overflow-y:hidden;margin:0px auto;width:100%;'></iframe>";
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
			if(!empty($value)) $htmlfields .= "&nbsp;<input type='button' style='background:red;color:#FFF' value='{$lan['delete']}' onclick='return confirmdelete();'>";
		}
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
	"<td colspan=\"{$colspan}\"><div class=\"righttop\"><a href=\"".h('setting')."\" target=\"_blank\">{$lan['help']}</a></div>{$title}</td>\n".
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
		if(strpos($field, 'orderby') !== false) {
			$l = str_replace('orderby', $lan['order'], $field);
		} elseif($field == 'dateline') {
			$l = $lan['time'];
		} else {
			$l = isset($lan[$field]) ? $lan[$field] : $field;
		}
		$setting = '';
		if($field == 'data' || $field == 'digest' || $field == 'paging') {
			$setting = "<select name='{$field}_type' id='{$field}_type'>".returnmodulefieldtype()."</select>";
			if(!empty($data[$field]['type'])) $setting .= "<script>$('#{$field}_type option[value={$data[$field]['type']}]').attr('selected',true);</script>";
		}
		if($field == 'title') {
			$setting .= "<input type='checkbox' name='iftitlestyle' id='iftitlestyle' value='1'><label for='iftitlestyle'>{$lan['style']}</label>";
			$setting .= "<input type='checkbox' name='ifinitial' id='ifinitial' value='1'><label for='ifinitial'>{$lan['initial']}</label>";
			if(!empty($data['title']['iftitlestyle'])) $setting .= "<script>$('#iftitlestyle').attr('checked', true); </script>";
			if(!empty($data['title']['ifinitial'])) $setting .= "<script>$('#ifinitial').attr('checked', true); </script>";
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
		$categoryfield = "<tr><td width=120>{$categoryalias}</td>".'<td class="categoryselect"><select id="category0" onchange="javascript:selectcategory(1, this.value)"></select><select id="category1" onchange="javascript:selectcategory(2, this.value)"></select><select id="category2" onchange="javascript:selectcategory(3, this.value)"></select><select id="category3" onchange="javascript:selectcategory(4, this.value)"></select><select id="category4" onchange="javascript:selectcategory(5, this.value)"></select><select id="category5" onchange="javascript:selectcategory(6, this.value)"></select><select id="category6" onchange="javascript:selectcategory(7, this.value)"></select><select id="category7" onchange="javascript:selectcategory(8, this.value)"></select><select id="category8" onchange="javascript:selectcategory(9, this.value)"></select><select id="category9" onchange="javascript:selectcategory(10, this.value)"></select><input type="hidden" id="category" name="category"><script language="javascript">$(document).ready(function(){selectcategory(0, 0);});</script></td></tr>';
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
			$htmlfields .= "<tr><td>{$commentalias}</td><td><iframe id='comments' onload='Javascript:SetframeHeight(\"comments\")' src='index.php?file=admincp&action=comments&id={$itemvalue['id']}&r=".random(6)."' frameborder='0' style='overflow-x:hidden;overflow-y:hidden;margin:0px auto;width:100%;'></iframe></td></tr>";
		} else {
			if($key == 'paging') $itemfieldvalue = $itemvalue['id'];
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
		@unlink(FORE_ROOT.$attach['filename']);
	}
	$db->delete('attachments', "itemid IN ({$ids})");
	$db->delete('filenames', "id IN ({$ids})");
	$array_sections = array();
	$array_categories = array();
	$array_editors = array();
	$query = $db->query_by('*', 'items', "id IN ($ids)");
	while($item = $db->fetch_array($query)) {
		$array_sections[] = $item['section'];
		$array_categories[] = $item['category'];
		$array_editors[] = $item['editor'];
		@unlink(FORE_ROOT.itemhtmlname($item['id'], 1, $item));
		if(!empty($item['picture'])) @unlink(FORE_ROOT.$item['picture']);
	}
	$db->delete('items', "id IN ({$ids})");
	$db->delete('item_exts', "id IN ({$ids})");
	refreshitemnum($array_categories, 'category');
	refreshitemnum($array_sections, 'section');
	refreshitemnum($array_editors, 'editor');
}

function refreshitemnum($ids, $type = 'category') {
	global $tablepre, $db;
	static $categories;
	if(is_array($ids)) {
		$ids = array_unique($ids);
	} else {
		$ids = array($ids);
	}
	if($type == 'category') {
		foreach($ids as $id) {
			if($id == 0) continue;
			if(empty($categories[$id])) $categories[$id] = getcategorycache($id);
			$allitems = $items = $db->get_by('COUNT(*)', 'items', "category='$id'");
			if(!empty($categories[$id]['subcategories'])) {
				foreach($categories[$id]['subcategories'] as $subcategory) {
					if(empty($categories[$subcategory])) $categories[$subcategory] = getcategorycache($subcategory);
					$allitems += $categories[$subcategory]['allitems'];
				}
			}
			$db->update('categories', array('items' => $items, 'allitems' => $allitems), "id='$id'");
		}
	} elseif($type == 'section') {
		foreach($ids as $id) {
			$items = $db->get_by('COUNT(*)', 'items', "section='$id'");
			$db->update('sections', array('items' => $items), "id='$id'");
		}
	} elseif($type == 'editor') {
		if(count($ids) == 0) {
			$ids = array();
			$query = $db->query_by('editor', 'admins');
			while($editor = $db->fetch_array($query)) {
				$ids[] = $editor['editor'];
			}
		}
		foreach($ids as $id) {
			$items = $db->get_by('COUNT(*)', 'items', "editor='$id'");
			$db->update('admins', array('items' => $items), "editor='$id'");
		}
	}
}

function showprocess($title, $processurl, $targeturl = '', $timeout = 100, $steps = array()) {
	global $smarty;
	$smarty->assign('title', $title);
	$smarty->assign('processurl', $processurl);
	$smarty->assign('targeturl', $targeturl);
	$smarty->assign('timeout', $timeout);
	$smarty->assign('steps', $steps);
	displaytemplate('admincp_process.htm');
	runinfo();
	aexit();
}

function returnmodulefieldtype() {
	global $lan;
	return "<option value='rich'>{$lan['richtext']}</option><option value='plain'>{$lan['plaintext']}</option>";
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
			$text = getxmltext($v['value']);
			$menu = array('url' => $url, 'text' => $text);
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
			$html .= "<li>$menuhtml</li>";
		}
		$html .= "</ul></div>";
	}
	return $html;
}

function rendermenulink($menu) {
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
	return "{$plus}<a href='{$menu['url']}'{$target} onclick='javascript:clicklink(this)'>{$menu['text']}</a>";
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
	$height = empty($ext['height']) ? '200px' : $ext['height'];
	$uploadimg = empty($ext['uploadimgurl']) ? '' : "upImgUrl:'{$ext['uploadimgurl']}',";
	$id = empty($ext['id']) ? $name.'_'.random(6) : $ext['id'];
	$class = '';
	if($type == 'rich') $class = " class=\"xheditor {hoverExecDelay:-1,{$uploadimg}upImgExt:'jpg,jpeg,gif,png',tools:'Source,Pastetext,|,Blocktag,Fontface,FontSize,Bold,Italic,Underline,Strikethrough,FontColor,BackColor,SelectAll,Removeformat,|,Align,List,Outdent,Indent,|,Link,Unlink,Anchor,Img,Hr,Table',loadCSS:'<style>body{font-size:14px;}</style>'}\"";
	return "<textarea id='$id' name='$name' style='width:$width;height:$height;'$class>$value</textarea>";
}

function h($params){
	$manual = $params;
	if(is_array($params)) $manual = $params['name'];
	return "http://www.akhtm.com/manual/{$manual}.htm?source=cp";
}

function loadlan($lanfile) {
	$lan = array();
	if(file_exists($lanfile)) {
		$fp = fopen($lanfile, 'r');
		while(!feof($fp)) {
			$line = trim(fgets($fp, 1024));
			if($line == '') continue;
			if(strpos($line, "\t") === false) continue;
			list($_k, $_l) = explode("\t", $line);
			$lan[$_k] = $_l;
		}
		fclose($fp);
	}
	return $lan;
}
?>
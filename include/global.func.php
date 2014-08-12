<?php
if(!defined('CORE_ROOT')) exit;
require_once(CORE_ROOT.'include/cache.func.php');
require_once(CORE_ROOT.'include/section.func.php');
require_once(CORE_ROOT.'include/category.func.php');
require_once(CORE_ROOT.'include/kv.func.php');
require_once(CORE_ROOT.'include/template.class.php');

function approot($app) {
	return AK_ROOT."configs/apps/$app/";
}

function writelog($log, $event = '', $good = 0) {
	if(function_exists('core_writelog')) {
		return core_writelog($log, $event, $good);
	} else {
		return true;
	}
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

function getakvariable($variable) {
	global $db;
	$value = $db->get_by('value', 'variables', "variable='".$db->addslashes($variable)."'");
	return $value;
}

function actionhookfile($key) {
	return AK_ROOT.'configs/hooks/'.$key.'.php';
}

function hookfunction($key, &$return, $params = array()) {
	if(file_exists(AK_ROOT.'configs/hooks/'.$key.'.php')) include(AK_ROOT.'configs/hooks/'.$key.'.php');
}

function appfile($app, $file) {
	return AK_ROOT.'configs/apps/'.$app.'/program/'.$file;
}

function parseparams() {
	$params = array();
	foreach($_SERVER['argv'] as $param) {
		if(strpos($param, '-') !== 0) continue;
		$param = substr($param, 1);
		$ep = strpos($param, '=');
		if($ep === false) {
			$params[$param] = 1;
		} else {
			$p = substr($param, 0, $ep);
			$v = substr($param, $ep + 1);
			$params[$p] = trim($v);
		}
	}
	return $params;
}

function createconfig($configs = array()) {
	$str_config = '';
	$array_config = array('dbtype', 'dbhost', 'dbuser', 'dbpw', 'dbname', 'tablepre', 'charset', 'timedifference', 'template_path', 'codekey', 'cookiepre', 'core_root', 'core_url');
	foreach($array_config as $config) {
		$value = false;
		if(isset($GLOBALS[$config])) $value = $GLOBALS[$config];
		if(isset($configs[$config])) $value = $configs[$config];
		if($config == 'core_root' && $value == './') $value = false;
		if($config == 'codekey' && $value == 'akcms') $value = false;
		if($config == 'cookiepre' && $value == 'akcms') $value = false;
		if($value === false) {
			if($config == 'ifdebug') {
				$value = '0';
			} elseif($config == 'template_path') {
				$value = 'ak';
			} elseif($config == 'codekey') {
				$value = random(6);
			} elseif($config == 'cookiepre') {
				$value = random(6);
			} else {
				continue;
			}
		}
		$str_config .= '$'.$config.' = \''.$value."';\n";
	}
	$str_config .= "\$ifdebug = 0;\n";
	$str_config = "<?php\n".$str_config."?>";
	writetofile($str_config, 'configs/config.inc.php');
}

function db($config = array(), $forcenew = 0) {
	global $dbtype, $db;
	if(!empty($db) && empty($forcenew)) return $db;
	if(empty($config)) {
		global $dbname, $dbhost, $dbuser, $dbpw, $charset;
		if($dbtype == 'mysql') {
			$config['type'] = 'mysql';
			$config['dbname'] = $dbname;
			$config['dbhost'] = $dbhost;
			$config['dbuser'] = $dbuser;
			$config['dbpw'] = $dbpw;
			$config['charset'] = $charset;
		} elseif($dbtype == 'sqlite') {
			$config['type'] = 'sqlite';
			$config['dbname'] = $dbname;
		} elseif($dbtype == 'sqlite3') {
			$config['type'] = 'sqlite3';
			$config['dbname'] = $dbname;
		} elseif($dbtype == 'pdo:sqlite2') {
			$config['type'] = 'pdo:sqlite';
			$config['version'] = 2;
			$config['dbname'] = $dbname;
		} elseif($dbtype == 'pdo:sqlite') {
			$config['type'] = 'pdo:sqlite';
			$config['version'] = 3;
			$config['dbname'] = $dbname;
		} elseif($dbtype == 'pdo:mysql') {
			$config['type'] = 'pdo:mysql';
			$config['dbname'] = $dbname;
			$config['dbhost'] = $dbhost;
			$config['dbuser'] = $dbuser;
			$config['dbpw'] = $dbpw;
			$config['charset'] = $charset;
		}
	}
	if($config['type'] == 'mysql') {
		require_once(CORE_ROOT.'include/db.mysql.php');
		$_db = new mysqlstuff($config);
	} elseif($config['type'] == 'sqlite') {
		require_once(CORE_ROOT.'include/db.sqlite.php');
		$_db = new sqlitestuff($config);
	} elseif($config['type'] == 'pdo:sqlite') {
		require_once(CORE_ROOT.'include/db.pdo.sqlite.php');
		$_db = new pdosqlitestuff($config);
	} elseif($config['type'] == 'pdo:mysql') {
		require_once(CORE_ROOT.'include/db.pdo.mysql.php');
		$_db = new pdomysqlstuff($config);
	} elseif($config['type'] == 'sqlite3') {
		require_once(CORE_ROOT.'include/db.sqlite3.php');
		$_db = new sqlite3stuff($config);
	}
	return $_db;
}
function akinclude($params) {
	global $template_path;
	if(!isset($params['pagevariables'])) {
		$pagevariables = array();
	} else {
		$pagevariables = $params['pagevariables'];
	}
	$pagevariables['subtemplate'] = 1;
	if(empty($params['expire'])) {
		echo render_template($params['file'], $pagevariables);
	} else {
		$params['type'] = 'template';
		$data = getcachedata($params);
		if($data == '') {
			$data = render_template($params['file'], $pagevariables);
			setcachedata($params, $data);
		}
		echo $data;
	}
}

function render_template($template, $pagevariables = array(), $createhtml = 0, $show = 0) {


	if($template == '') {
		if(isset($pagevariables['template'])) {
			$template = $pagevariables['template'];
		} else {
			return false;
		}
	}
	global $template_path, $tpl, $user, $thetime, $lan, $lr, $header_charset, $globalvariables, $sections, $setting_storemethod, $homepage, $setting_defaultfilename, $sysname, $sysedition, $settings;
	if(empty($pagevariables['subtemplate']) && empty($pagevariables['systemplate']) && strpos($template, '/') === false) $template = ','.$template;
	createpathifnotexists(AK_ROOT."cache/foretemplates");
	$tpl = new tpl(array(AK_ROOT."configs/templates/$template_path", AK_ROOT.'templates/fore'), AK_ROOT.'cache/foretemplates');
	$GLOBALS['tpl'] = $tpl;
	
	$variables = array(
		'charset' => $header_charset,
		'thetime' => $thetime,
		'page' => 1,
		'home' => substr($homepage, 0, -1)
	);

	if(isset($_GET['page']) && a_is_int($_GET['page'])) $variables['page'] = $_GET['page'];
	if(isset($pagevariables['page'])) $variables['page'] = $pagevariables['page'];
	$globalvariables = getcache('globalvariables');
	if(!empty($globalvariables)) {
		foreach($globalvariables as $key => $v) {
			$variables['v_'.$key] = $v;
		}
	}
	foreach($_GET as $key => $value) {
		$variables['get_'.$key] = ak_htmlspecialchars($value);
		$variables['get_d_'.$key] = $value;
		$variables['get_u_'.$key] = urlencode($value);
	}
	foreach($_COOKIE as $key => $value) {
		$variables['cookie_'.$key] = ak_htmlspecialchars($value);
		$variables['cookie_d_'.$key] = $value;
	}
	foreach($settings as $k => $v) {
		$variables['setting_'.$k] = $v;
	}
	$variables['lan'] = $lan;
	$tpl->assign($pagevariables);
	$tpl->assign($variables);
	
	require_once CORE_ROOT.'include/getdata.func.php';
	$function = 'akinclude,akincludeurl,getitems,getsections,gettexts,getcategories,getcomments,getlists,monitor,getindexs,getpaging,ifhassubcategories,getattachments,getkeywords,getsqls,getinfo,getuser,getpictures,akecho,gettime';
	$tpl->regfunction($function);
	$apps = getcache('templateplugins');
	if(!empty($apps)) {
		foreach($apps as $app) {
			$tpl->regfunction($app);
		}
	}
	$html = $tpl->render($template);
	if(empty($pagevariables['subtemplate'])) $html = renderhtml($html, $pagevariables);
	if(!empty($pagevariables['html']) && !empty($createhtml)) {
		$filename = $pagevariables['htmlfilename'];
		$_s = calfilenamefromurl($filename);
		if(strpos($_s, '.') === false) {
			$filename .= '/'.$setting_defaultfilename;
		} elseif(substr($_s, -1) == '/') {
			$filename .= $setting_defaultfilename;
		}
		writetofile($html, $filename);
	}
	if($show > 0) echo $html;
	return $html;
}

function get_text_data($itemid, $page, $variables = array(), $category = array()) {
	global $db;
	$v = $db->get_by('*', 'texts', "itemid=$itemid AND page=$page");
	$text = $v['text'];
	$modules = getcache('modules');
	$module = $modules[$category['module']];
	if(!empty($module['data']['fields']['paging']['type']) && $module['data']['fields']['paging']['type'] == 'plain') {
		$v['pagedata'] = nl2br($text);
	} else {
		$v['pagedata'] = $text;
	}
	$v['itempage'] = $page;
	$v['htmlfilename'] = '';
	$filename = itempagehtmlname($itemid, $page, $variables, $category);
	if($filename != '') $v['htmlfilename'] = FORE_ROOT.$filename;
	return $v;
}

function get_item_data($id, $template = '', $params = array(), $uid = 0) {
	global $template_path, $db, $tablepre, $lan, $thetime, $system_root, $lr, $setting_homepage, $setting_ifhtml, $sections, $setting_storemethod, $setting_richtext, $homepage, $attachurl;
	if(!a_is_int($id)) return false;
	$variables['_pagetype'] = 'item';
	$variables['_pageid'] = $id;
	if(!$item = $db->get_by('*', 'items', "id='$id'")) return array();
	$categorycache = getcategorycache($item['category']);
	$modules = getcache('modules');
	if(!empty($categorycache)) {
		if(!isset($modules[$categorycache['module']])) return false;
		$module = $modules[$categorycache['module']];
	} else {
		if($item['category'] > 0) return false;
	}
	if(!empty($template)) {
		$variables['template'] = $template;
	} elseif(!empty($params['ver']) && $params['ver'] > 1) {
		$variables['template'] = $categorycache['template'.$params['ver']];
	} elseif(!empty($params['itempage'])) {
		$variables['template'] = $categorycache['pagetemplate'];
	} else {
		if($item['template'] == '') {
			$variables['template'] = $categorycache['itemtemplate'];
		} else {
			$variables['template'] = $item['template'];
		}
	}
	$templatehtml = readfromfile(AK_ROOT.'configs/templates/'.$template_path.'/,'.$variables['template']);
	if(empty($params['itempage'])) {
		$sql = "SELECT text FROM {$tablepre}_texts WHERE itemid='{$id}' AND page='0'";
		$text = $pagedata = $db->get_field($sql);
		$itempage = 1;
		$subtitle = '';
	} else {
		$text = $subtitle = $pagedata = '';
		$itempage = $params['itempage'];
		$sql = "SELECT * FROM {$tablepre}_texts WHERE itemid='{$id}' AND page IN (0,{$params['itempage']})";
		$query = $db->query($sql);
		while($record = $db->fetch_array($query)) {
			if($record['page'] == 0) {
				$text = $pagedata = $record['text'];
			} else {
				$subtitle = $record['subtitle'];
				$pagedata = $record['text'];
				if(!empty($module['data']['fields']['paging']['type']) && $module['data']['fields']['paging']['type'] == 'plain') $pagedata = nl2br($pagedata);
			}
		}
	}
	$texttitle = $item['title'];
	$textshorttitle = empty($item['shorttitle']) ? $texttitle : $item['shorttitle'];
	$title = htmltitle($texttitle, $item['titlecolor'], $item['titlestyle']);
	$shorttitle = htmltitle($textshorttitle, $item['titlecolor'], $item['titlestyle']);
	$text = renderkeywords($text, $item['keywords']);
	$sections = getcache('sections');
	$section = !empty($sections[$item['section']]) ? $sections[$item['section']] : array();
	if(empty($item['dateline'])) $item['dateline'] = 0;
	list($y, $m, $d, $h, $i, $s) = explode(',', date('Y,m,d,H,i,s', $item['dateline']));
	if($item['lastupdate'] == 0) $item['lastupdate'] = $item['dateline'];
	list($last_y, $last_m, $last_d, $last_h, $last_i, $last_s) = explode(',', date('Y,m,d,H,i,s', $item['lastupdate']));
	for($k = 1; $k <= 4; $k ++) {
		$variables['url'.$k] = itemurl($id, $k, $item, $categorycache);
	}
	$variables['url'] = $variables['url1'];
	if(!empty($item['ext'])) {
		$itemextvalues = ak_unserialize($db->get_by('value', 'item_exts', "id='{$id}'"));
		if(is_array($itemextvalues)) {
			$variables = array_merge($variables, $itemextvalues);
		}
	}
	if($item['category'] == 0) {
		if($item['filename'] == '') {
			$variables['html'] = 0;
		} else {
			$variables['html'] = 1;
		}
	} elseif($categorycache['html'] == 1 || ($categorycache['html'] == 0 && $setting_ifhtml == 1)) {
		$variables['html'] = 1;
	} else {
		$variables['html'] = 0;
	}
	$variables['id'] = $id;
	$variables['title'] = $title;
	$variables['shorttitle'] = $shorttitle;
	$variables['texttitle'] = $texttitle;
	$variables['textshorttitle'] = $textshorttitle;
	if(!empty($module['data']['fields']['data']['type']) && $module['data']['fields']['data']['type'] == 'plain') {
		$variables['data'] = nl2br($text);
	} else {
		$variables['data'] = $text;
	}
	$variables['keywords'] = tidyitemlist($item['keywords'], ',', 0);
	$variables['category'] = $item['category'];
	$variables['aimurl'] = $item['aimurl'];
	$variables['digest'] = $item['digest'];
	if(!empty($item['category']) && $item['category'] > 0) {
		$variables['categoryname'] = $categorycache['category'];
		$variables['categoryurl'] = getcategoryurl($item['category']);
		$variables['categorypath'] = $categorycache['path'];
		$variables['categoryalias'] = $categorycache['alias'];
		$variables['categorydescription'] = $categorycache['description'];
		$variables['categorykeywords'] = $categorycache['keywords'];
		$variables['categoryup'] = $categorycache['categoryup'];
	} else {
		$variables['title'] = $variables['aimurl'];
		$variables['description'] = $variables['digest'];
	}
	$variables['section'] = $item['section'];
	if(!empty($item['section']) && !empty($section)) {
		$variables['sectionname'] = $section['section'];
		$variables['sectionalias'] = $section['alias'];
		$variables['sectiondescription'] = $section['description'];
		$variables['sectionkeywords'] = $section['keywords'];
	}
	$variables['editor'] = $item['editor'];
	$variables['author'] = $item['author'];
	$variables['price'] = $item['price'];
	if(strpos($templatehtml, '$author_encode') !== false) $variables['author_encode'] = urlencode($item['author']);
	$variables['source'] = $item['source'];
	$variables['picture'] = pictureurl($item['picture'], $attachurl);
	$variables['pageview'] = $item['pageview'];
	if(empty($module['data']['fields']['digest']['richtext'])) $variables['digest'] = nl2br($item['digest']);
	$variables['y'] = $y;
	$variables['m'] = $m;
	$variables['d'] = $d;
	$variables['h'] = $h;
	$variables['i'] = $i;
	$variables['s'] = $s;
	$variables['dateline'] = $item['dateline'];
	$variables['last_y'] = $last_y;
	$variables['last_m'] = $last_m;
	$variables['last_d'] = $last_d;
	$variables['last_h'] = $last_h;
	$variables['last_i'] = $last_i;
	$variables['draft'] = $item['draft'];
	$variables['last_s'] = $last_s;
	$variables['commentnum'] = $item['commentnum'];
	$variables['scorenum'] = $item['scorenum'];
	$variables['aimurl'] = $item['aimurl'];
	$variables['totalscore'] = $item['totalscore'];
	$variables['avgscore'] = $item['avgscore'];
	$variables['filename'] = $item['filename'];
	$variables['attach'] = $item['attach'];
	$variables['orderby'] = $item['orderby'];
	$variables['orderby2'] = $item['orderby2'];
	$variables['orderby3'] = $item['orderby3'];
	$variables['orderby4'] = $item['orderby4'];
	$variables['orderby5'] = $item['orderby5'];
	$variables['orderby6'] = $item['orderby6'];
	$variables['orderby7'] = $item['orderby7'];
	$variables['orderby8'] = $item['orderby8'];
	$variables['pv1'] = $item['pv1'];
	$variables['pv2'] = $item['pv2'];
	$variables['pv3'] = $item['pv3'];
	$variables['pv4'] = $item['pv4'];
	$variables['string1'] = $item['string1'];
	$variables['string2'] = $item['string2'];
	$variables['string3'] = $item['string3'];
	$variables['string4'] = $item['string4'];
	$variables['itempage'] = $itempage;
	$variables['subtitle'] = $subtitle;
	$variables['pagedata'] = $pagedata;
	$variables['pagenum'] = $item['pagenum'];
	$variables['tags'] = $item['tags'];
	if($variables['html']) {
		$_html = itemhtmlname($item['id'], 1, $item, $categorycache);
		$variables['htmlfilename'] = FORE_ROOT.$_html;
		$variables['currenturl'] = $_html;
		if($item['category'] > 0) {
			for($i = 2; $i <= 4; $i ++) {
				$variables['htmlfilename'.$i] = '';
				$_itemhtmlname = itemhtmlname($item['id'], $i, $item, $categorycache);
				if($_itemhtmlname != '') $variables['htmlfilename'.$i] = FORE_ROOT.$_itemhtmlname;
				$variables['template'.$i] = $categorycache['template'.$i];
			}
		}
	}
	if($uid) {
		$relations = $db->query_by('action', 'actions', "uid=$uid AND iid=$id");
		while($relation = $db->fetch_array($relations)) {
			$variables[$relation['action']] = $relation['action'];
		}
	}
	return $variables;
}

function createkeywordhtml($keywords) {
}

function batchhtml($ids, $params = array()) {
	if(is_numeric($ids)) $ids = array($ids);
	$categories = array();
	$GLOBALS['batchcreateitemflag'] = 1;
	deletetask('createhtml_item');
	foreach($ids as $id) {
		addtask('createhtml_item', "$id\t\t1");
	}
	while($task = gettask('createhtml_item')) {
		list($id, $filename, $page) = explode("\t", $task);
		if(strpos($filename, '?') !== false) continue;
		if(!isset($lastid) || $lastid != $id) $variables = get_item_data($id);
		if(empty($variables)) return false;
		if(!empty($filename)) $variables['htmlfilename'] = $filename;
		$variables['page'] = $page;
		$c = $variables['category'];
		if(empty($variables)) continue;
		if(!empty($c)) {
			if(empty($categories[$c])) $categories[$c] = getcategorycache($c);
			$cc = $categories[$c];
		}
		if($variables['html']) {
			if($page <= 1) $GLOBALS['index_work'] = "item\n".$id."\n".$variables['htmlfilename'];
			if(empty($params['onlypage'])) {
				$html = render_template($variables['template'], $variables, 1);
				if($html === false) return false;
				for($i = 2; $i <= 4; $i ++) {
					if(empty($variables['template'.$i]) || empty($variables['htmlfilename'.$i])) continue;
					$html = render_template($variables['template'.$i], $variables, 1);
				}
			}
			if(empty($params['nopage'])) {
				if($variables['pagenum'] > 0) {
					for($i = 1; $i <= $variables['pagenum']; $i ++) {
						$textdata = get_text_data($id, $i, $variables, $cc);
						if($textdata['htmlfilename'] == '') continue;
						unset($textdata['id']);
						$textdata += $variables;
						$html = render_template($cc['pagetemplate'], $textdata, 1);
					}
				}
			}
		}
		unset($GLOBALS['index_work']);
		$lastid = $id;
	}
	unset($GLOBALS['batchcreateitemflag']);
}

function getkeywordscache() {
	global $codekey;
	$_keywords = array();
	$file = AK_ROOT.'configs/keywords.txt';
	if(!file_exists($file)) return $_keywords;
	if($fp = @fopen($file, 'r')) {
		while(!feof($fp)) {
			$_line = trim(fgets($fp));
			if(empty($_line)) continue;
			$_f = explode("\t", $_line);
			if(!isset($_f[0]) || !isset($_f[1])) continue;
			if(!isset($_f[2])) $_f[2] = '';
			$_keywords[] = $_f;
		}
		fclose($fp);
	}
	return $_keywords;
}

function htmlname($id, $category = 0, $dateline = 0, $filename = '') {
	$html = core_htmlname($id, $category, $dateline, $filename);
	return $html;
}

function keywordfilename($keyword, $se = array()) {
	global $db;
	if(empty($se)) $se = getsedata($keyword['sid']);
	$storemethod = $se['storemethod'];
	$variables['keyword'] = $keyword['keyword'];
	return calstoremethod($storemethod, $variables);
}

function getsedata($sid) {
	global $db;
	if(!a_is_int($sid)) return false;
	$se = $db->get_by('*', 'ses', "id='$sid'");
	return $se + ak_unserialize($se['value']);
}

function keywordurl($keyword, $se = array()) {
	global $homepage;
	$filename = keywordfilename($keyword, $se);
	$paths = explode('/', $filename);
	foreach($paths as $k => $v) {
		$paths[$k] = urlencode($v);
	}
	$filename = implode('/', $paths);
	return $homepage.$filename;
}

function itemurl($id, $version = 1, $item = array(), $cc = array()) {
	global $homepage;
	return $homepage.itemhtmlname($id, $version, $item, $cc);
}
function itemhtmlname($id, $version = 1, $item = array(), $cc = array()) {
	global $setting_htmlexpand, $db;
	if(empty($item)) $item = $db->get_by('*', 'items', "id=$id");
	$category = $item['category'];
	$filename = isset($item['filename']) ? $item['filename'] : '';
	if(empty($cc)) $cc = getcategorycache($category);
	if($category > 0 && empty($cc)) return false;
	list($year, $month, $day) = explode(' ', date('Y m d', $item['dateline']));
	if($category == 0) {
		$_path = $fullpath = '.';
	} else {
		$fullpath = $cc['fullpath'];
		$_path = $cc['path'];
	}
	if($version == 1) {
		$storemethod = $cc['storemethod'];
	} else {
		$storemethod = $cc['storemethod'.$version];
	}
	$path = str_replace('[categorypath]', $fullpath, $storemethod);
	$path = str_replace('[path]', $_path, $path);
	if(isset($item['title'])) $path = str_replace('[title]', $item['title'], $path);
	$path = str_replace('[y]', $year, $path);
	$path = str_replace('[m]', $month, $path);
	$path = str_replace('[d]', $day, $path);
	$path = str_replace('[id]', $id, $path);
	if(isset($item['author'])) $path = str_replace('[author]', space2dash($item['author']), $path);
	$path = preg_replace('/\[id\/([\d]+)\]/e', "ceil($id/\\1)", $path);
	if(empty($filename)) {
		$filename = "{$id}{$setting_htmlexpand}";
	} else {
		if(preg_match('/^\//i', $filename)) {
			return substr($filename, 1);
		}
	}
	$path = str_replace('[f]', $filename, $path);
	return $path;
}
function itempageurl($id, $page = 0, $item = array(), $cc = array()) {
	global $homepage;
	return $homepage.itempagehtmlname($id, $page, $item, $cc);
}
function itempagehtmlname($id, $page, $item = array(), $cc = array()) {
	global $db, $setting_htmlexpand;
	if(empty($item)) $item = $db->get_by('*', 'items', "id=$id");
	$category = $item['category'];
	if(empty($cc)) $cc = getcategorycache($category);
	if($category > 0 && empty($cc)) return false;
	list($year, $month, $day) = explode(' ', date('Y m d', $item['dateline']));
	if($category == 0) {
		$_path = $fullpath = '.';
	} else {
		$fullpath = $cc['fullpath'];
		$_path = $cc['path'];
	}
	$storemethod = $cc['pagestoremethod'];
	$path = str_replace('[categorypath]', $fullpath, $storemethod);
	$path = str_replace('[path]', $_path, $path);
	$path = str_replace('[y]', $year, $path);
	$path = str_replace('[m]', $month, $path);
	$path = str_replace('[d]', $day, $path);
	$path = str_replace('[id]', $id, $path);
	$path = str_replace('[page]', $page, $path);
	$filename = $item['filename'];
	if(empty($filename)) {
		$filename = "{$id}{$setting_htmlexpand}";
	} else {
		if(preg_match('/^\//i', $filename)) {
			return substr($filename, 1);
		}
	}
	$path = str_replace('[f]', $filename, $path);
	$path = preg_replace('/\[id\/([\d]+)\]/e', "ceil($id/\\1)", $path);
	return $path;
}

function aexit($text = '') {
	global $db;
	if(isset($db)) $db->close();
	exit(''.$text);
}
function renderkeywords($text, $keywords) {
	global $setting_keywordslink, $setting_globalkeywordstemplate;
	$replace = array();
	$to = array();
	if(!empty($setting_globalkeywordstemplate)) {
		$globalkeywords = getkeywordscache();
		foreach($globalkeywords as $_k) {
			$replace[] = $_k[0];
			$_to = str_replace('[url]', $_k[1], $setting_globalkeywordstemplate);
			$_to = str_replace('[keyword]', $_k[0], $_to);
			$_to = str_replace('[digest]', $_k[2], $_to);
			$to[] = $_to;
		}
	}
	if(!empty($setting_keywordslink)) {
		if($keywords != '') {
			$keywords = tidyitemlist($keywords, ',', 0);
			$keywords = explode(',', $keywords);
			$keywords = sortbylength($keywords);
		} else {
			$keywords = array();
		}
		foreach($keywords as $keyword) {
			$keyword = trim($keyword);
			if(empty($keyword)) continue;
			if(in_array($keyword, $replace)) continue;
			$_to = ak_replace('[keywordinurl]', urlencode($keyword), $setting_keywordslink);
			$_to = ak_replace('[keyword]', $keyword, $_to);
			$replace[] = $keyword;
			$to[] = $_to;
		}
	}
	foreach($replace as $_k => $_v) {
		$text = replacekeyword($text, $_v, $to[$_k], 1, 1);
	}
	return $text;
}

function refreshcommentnum($id, $refreshtime = 0) {
	global $db, $thetime;
	$commentnum = $db->get_by('COUNT(*) as c', 'comments', "itemid='$id'");
	$value = array('commentnum' => $commentnum);
	if($refreshtime) $value['lastcomment'] = $thetime;
	$db->update('items', $value, "id='$id'");
}

function getidbyfilename($filename) {
	global $db;
	$id = $db->get_by('id', 'filenames', "filename='$filename'");
	return $id;
}

function updateitemscore($id) {
	global $db;
	$result = $db->get_by('AVG(score) as a,SUM(score) as s, COUNT(*) as c', 'scores', "itemid=$id");
	if(empty($result['s'])) $result['s'] = 0;
	if(empty($result['a'])) $result['a'] = 0;
	$value = array(
		'totalscore' => $result['s'],
		'scorenum' => $result['c'],
		'avgscore' => $result['a']
	);
	$db->update('items', $value, "id=$id");
}

function pickpicture($html, $baseurl = '') {
	preg_match_all("/<img(.*?)src=(.+?)['\" >]+/is", $html, $match);
	$pics = array();
	foreach($match[2] as $pic) {
		$pic = str_replace('"', '', $pic);
		$pic = str_replace('\'', '', $pic);
		if(!empty($pic)) break;
	}
	if(empty($pic)) return '';
	return calrealurl($pic, $baseurl);
}

function copypicturetolocal($html, $config, $task = 0) {
	global $homepage;
	if($html == '') return '';
	$category = $config['category'];
	preg_match_all("/<img(.*?)src=(.+?)['\" >]+/is", $html, $match);
	$pics = array();
	foreach($match[2] as $pic) {
		$pic = str_replace('"', '', $pic);
		$pic = str_replace('\'', '', $pic);
		$pics[] = $pic;
	}
	$pics = array_unique($pics);
	if(strpos($html, '<') === false) $html = calrealurl($html, $config['itemurl']);
	if(substr($html, 0, 7) == 'http://') $pics[] = $html;
	foreach($pics as $pic) {
		$picname = get_upload_filename($pic, 0, $category, 'image');
		$pictureurl = calrealurl($pic, $config['itemurl']);
		if(strpos($pictureurl, $homepage) !== false) continue;
		if(!empty($task)) {
		} else {
			$picturedata = readfromurl($pictureurl);
			writetofile($picturedata, FORE_ROOT.$picname);
			require_once(CORE_ROOT.'include/image.func.php');
			operateuploadpicture(FORE_ROOT.$picname, $category);
		}
		$html = str_replace($pic, $homepage.$picname, $html);
	}
	return $html;
}

function calrealurl($target, $baseurl = '') {
	if(strpos($target, '://') !== false) return $target;
	if(substr($target, 0, 1) == '/') {
		$domain = getdomain($baseurl);
		return 'http://'.$domain.'/'.substr($target, 1);
	} else {
		$urlpath = geturlpath($baseurl);
		return $urlpath.$target;
	}
}

function get_upload_filename($filename, $id, $category, $type = 'attach') {
	global $setting_attachmethod, $setting_imagemethod, $setting_previewmethod, $setting_attachthumbmethod, $thetime;
	if($type == 'attach') {
		$return = $setting_attachmethod;
	} elseif($type == 'image') {
		$return = $setting_imagemethod;
	} elseif($type == 'preview') {
		$return = $setting_previewmethod;
	} elseif($type == 'thumb') {
		$return = $setting_attachthumbmethod;
	}
	list($y, $m, $d) = explode('-', date('Y-m-d', $thetime));
	$c = getcategorycache($category);
	if($type != 'thumb') {
		$filename = random(6).'.'.fileext($filename);
	} else {
		$filename = basename($filename);
	}
	$md5 = ak_md5($filename, 1);
	$variable = array(
		'y' => $y,
		'm' => $m,
		'd' => $d,
		'f' => $filename,
		'id' => $id,
		'categorypath' => $c['fullpath'],
		'path' => $c['path'],
		'hash1' => substr($md5, 0, 1),
		'hash2' => substr($md5, 1, 1),
		'hash3' => substr($md5, 2, 1),
	);
	$return = calstoremethod($return, $variable);
	return $return;
}

function get_spider_filename($filename, $type = 'attach') {
	$md5 = md5($filename);
	$variable = array(
		'f' => substr($md5, 0, 12).'.'.fileext($filename),
		'hash1' => substr($md5, 0, 1),
		'hash2' => substr($md5, 1, 1),
		'hash3' => substr($md5, 2, 1),
	);
	$return = calstoremethod('pictures/s/[hash1]/[hash2]/[f]', $variable);
	return $return;
}

function filter($id, $input, $filters = array()) {
	if(empty($id)) return $input;
	if(empty($filters)) $filters = getcache('filters');
	if(is_array($input)) {
		foreach($input as $k => $v) {
			$input[$k] = filter($id, $v, $filters);
		}
		return $input;
	}
	if(!isset($filters[$id])) return $input;
	$filterrule = $filters[$id];
	$filterrules = explode("\n", $filterrule);
	foreach($filterrules as $rule) {
		if(substr($rule, 0, 1) == '#') continue;
		$rule = trim($rule, "\r\n");
		if(substr($rule, 0, 4) == 'php:' && substr($rule, -1) == ';') {
			$rule = substr($rule, 4);
			if(is_string($input)) {
				$rule = str_replace('$input', "'".str_replace("'", "\'", $input)."'", $rule);
			}
			$input = eval("return $rule");
		} elseif(substr($rule, 0, 8) == 'include:') {
			$newid = substr($rule, 8);
			$input = filter($newid, $input, $filters);
		} elseif(substr($rule, 0, 8) == 'replace:') {
			$rule = substr($rule, 8);
			$rule = str_replace('[|]', '[#]', $rule);
			if(substr_count($rule, '|') != 1) continue;
			list($replace, $to) = explode('|', $rule);
			$replace = str_replace('[#]', '|', $replace);
			$to = str_replace('[#]', '|', $to);
			$replace = str_replace('[n]', "\n", $replace);
			$to = str_replace('[n]', "\n", $to);
			$input = str_replace($replace, $to, $input);
		} elseif(substr($rule, 0, 13) == 'preg_replace:') {
			$rule = substr($rule, 13);
			$rule = str_replace('[|]', '[#]', $rule);
			if(substr_count($rule, '|') != 1) continue;
			list($replace, $to) = explode('|', $rule);
			$replace = str_replace('[|]', '[#]', $replace);
			$to = str_replace('[|]', '[#]', $to);
			$input = preg_replace("/$replace/Uis", $to, $input);
		} elseif(substr($rule, 0, 5) == 'keep:') {
			$rule = substr($rule, 5);
			if(strpos($input, $rule) === false) $input = false;
		} elseif(substr($rule, 0, 6) == 'clear:') {
			$rule = substr($rule, 6);
			if(strpos($input, $rule) !== false) $input = false;
		}
	}
	return $input;
}

function getmaxpage($itemid) {
	global $db;
	$result = $db->get_by('max(page) as m', 'texts', "itemid='{$itemid}'");
	if(is_null($result)) return 0;
	return $result;
}

function calpagefilename($item, $category, $page) {
	list($y, $m, $d) = explode('-', date('Y-m-d', $item['dateline']));
	$variables = array(
		'id' => $item['id'],
		'categorypath' => $category['fullpath'],
		'path' => $category['path'],
		'page' => $page,
		'y' => $y,
		'm' => $m,
		'd' => $d,
	);
	$filename = calstoremethod($category['pagestoremethod'], $variables);
	return FORE_ROOT.$filename;
}

function calstoremethod($template, $variables) {
	foreach($variables as $k => $v) {
		$template = str_replace("[$k]", $v, $template);
	}
	return $template;
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
?>

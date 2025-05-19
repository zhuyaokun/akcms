<?php
if(!defined('CORE_ROOT')) exit;
require_once(CORE_ROOT.'include/cache.func.php');
require_once(CORE_ROOT.'include/section.func.php');
require_once(CORE_ROOT.'include/category.func.php');
require_once(CORE_ROOT.'include/kv.func.php');
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
	eventlog("$str_config");
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
		$db = new mysqlstuff($config);
	} elseif($config['type'] == 'sqlite') {
		require_once(CORE_ROOT.'include/db.sqlite.php');
		$db = new sqlitestuff($config);
	} elseif($config['type'] == 'pdo:sqlite') {
		require_once(CORE_ROOT.'include/db.pdo.sqlite.php');
		$db = new pdosqlitestuff($config);
	} elseif($config['type'] == 'pdo:mysql') {
		require_once(CORE_ROOT.'include/db.pdo.mysql.php');
		$db = new pdomysqlstuff($config);
	}
	return $db;
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
		echo render_template($pagevariables, $params['file']);
	} else {
		$params['type'] = 'template';
		$data = getcachedata($params);
		if($data == '') {
			$data = render_template($pagevariables, $params['file']);
			setcachedata($params, $data);
		}
		echo $data;
	}
}

function includetemplateplugins() {
	global $html_smarty, $plugins;
	$plugins = getcache('plugins');
	if(empty($plugins)) return;
	foreach($plugins as $plugin) {
		$pluginkey = str_replace('.template.php', '', $plugin);
		require_once(AK_ROOT.'plugins/'.$plugin);
		$html_smarty->register_function($pluginkey, $pluginkey);
	}
}

function render_template($pagevariables, $template = '', $createhtml = 0) {
	if(!empty($pagevariables['html']) && !empty($createhtml) && strpos($pagevariables['htmlfilename'], '?') !== false) return false;
	if($template == '') {
		if(isset($pagevariables['template'])) {
			$template = $pagevariables['template'];
		} else {
			return false;
		}
	}
	global $template_path, $smarty, $thetime, $lr, $header_charset, $globalvariables, $sections, $setting_storemethod, $html_smarty, $homepage, $setting_defaultfilename, $sysname, $sysedition;
	if(empty($pagevariables['subtemplate']) && empty($pagevariables['systemplate'])) $template = ','.$template;
	$templatefile = $template;
	if(strpos($template, '/') === false) $templatefile = AK_ROOT."configs/templates/$template_path/".$template;
	if(!file_exists($templatefile)) {
		if(substr($template, 0, 1) == ',') $template = substr($template, 1);
		aexit($template.' lose.<br /><a href="http://www.akhtm.com/manual/template-lose.htm" target="_blank">help</a>');
	}
	require_once CORE_ROOT.'include/smarty/libs/Smarty.class.php';
	$html_smarty = new Smarty;
	$sections = getcache('sections');
	$globalvariables = getcache('globalvariables');
	require_once CORE_ROOT.'include/getdata.func.php';
	$html_smarty->template_dir = AK_ROOT."configs/templates/$template_path";
	$html_smarty->compile_dir = AK_ROOT."cache/foretemplates";
	$html_smarty->config_dir = AK_ROOT."configs/";
	$html_smarty->cache_dir = AK_ROOT."cache/";
	$html_smarty->left_delimiter = "<{";
	$html_smarty->right_delimiter = "}>";
	$html_smarty->error_reporting = true;
	$html_smarty->assign('charset', $header_charset);
	$html_smarty->assign('pagevariables', $pagevariables);
	$html_smarty->assign('thetime', $thetime);
	$functions = array('akinclude', 'akincludeurl', 'getitems', 'gettexts', 'getcategories', 'getcomments', 'getlists', 'monitor', 'getindexs', 'getpaging','ifhassubcategories', 'getattachments', 'getkeywords', 'getsqls', 'getinfo', 'getuser', 'getpictures', 'akecho');
	foreach($functions as $function) {
		$html_smarty->registerPlugin('function', $function, $function);
	}
	includetemplateplugins();
	$html_smarty->assign('home', substr($homepage, 0, -1));
	if(!empty($globalvariables)) {
		foreach($globalvariables as $key => $v) {
			$html_smarty->assign('v_'.$key, $v);
		}
	}
	foreach($pagevariables as $key => $value) {
		$html_smarty->assign($key, $value);
	}
	foreach($_GET as $key => $value) {
		$html_smarty->assign('get_'.$key, ak_htmlspecialchars($value));
		$html_smarty->assign('get_d_'.$key, $value);
		$html_smarty->assign('get_u_'.$key, urlencode($value));
	}
	foreach($_COOKIE as $key => $value) {
		$html_smarty->assign('cookie_'.$key, ak_htmlspecialchars($value));
		$html_smarty->assign('cookie_d_'.$key, $value);
	}
	$html_smarty->assign('page', '1');
	if(isset($_GET['page'])) $html_smarty->assign('page', htmlspecialchars($_GET['page']));
	if(isset($pagevariables['page'])) $html_smarty->assign('page', $pagevariables['page']);
	$text = $html_smarty->fetch($template, true, false);
	if(empty($pagevariables['subtemplate'])) $text = renderhtml($text, $pagevariables);
	if(!empty($pagevariables['html']) && !empty($createhtml)) {
		$filename = $pagevariables['htmlfilename'];
		$_s = calfilenamefromurl($filename);
		if(strpos($_s, '.') === false) {
			$filename .= '/'.$setting_defaultfilename;
		} elseif(substr($_s, -1) == '/') {
			$filename .= $setting_defaultfilename;
		}
		writetofile($text, $filename);
	}
	return $text;
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
	$v['htmlfilename'] = FORE_ROOT.itempagehtmlname($itemid, $page, $variables, $category);
	return $v;
}

function get_item_data($id, $template = '', $params = array()) {
	global $template_path, $smarty, $db, $tablepre, $lan, $thetime, $system_root, $lr, $header_charset, $setting_homepage, $setting_ifhtml, $sections, $setting_storemethod, $html_smarty, $setting_richtext, $homepage, $attachurl;
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
		$sql = "SELECT * FROM {$tablepre}_texts WHERE itemid='{$id}' AND page IN ('0','{$params['itempage']}')";
		$query = $db->query($sql);
		while($record = $db->fetch_array($query)) {
			if($record['page'] == 0) {
				$text = $pagedata = $record['text'];
			} else {
				$itempage = $params['itempage'];
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
	if(!empty($item['category']) && $item['category'] > 0) {
		$variables['categoryname'] = $categorycache['category'];
		$variables['categoryurl'] = getcategoryurl($item['category']);
		$variables['categorypath'] = $categorycache['path'];
		$variables['categoryalias'] = $categorycache['alias'];
		$variables['categorydescription'] = $categorycache['description'];
		$variables['categorykeywords'] = $categorycache['keywords'];
		$variables['categoryup'] = $categorycache['categoryup'];
	}
	$variables['section'] = $item['section'];
	if(!empty($item['section'])) {
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
	$variables['digest'] = $item['digest'];
	if(empty($module['data']['fields']['digest']['richtext'])) $variables['digest'] = nl2br($item['digest']);
	$variables['aimurl'] = $item['aimurl'];
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
	$variables['last_s'] = $last_s;
	$variables['commentnum'] = $item['commentnum'];
	$variables['scorenum'] = $item['scorenum'];
	$variables['aimurl'] = $item['aimurl'];
	$variables['totalscore'] = $item['totalscore'];
	$variables['avgscore'] = $item['avgscore'];
	$variables['attach'] = $item['attach'];
	$variables['orderby'] = $item['orderby'];
	$variables['orderby2'] = $item['orderby2'];
	$variables['orderby3'] = $item['orderby3'];
	$variables['orderby4'] = $item['orderby4'];
	$variables['pv1'] = $item['pv1'];
	$variables['pv2'] = $item['pv2'];
	$variables['pv3'] = $item['pv3'];
	$variables['pv4'] = $item['pv4'];
	$variables['itempage'] = $itempage;
	$variables['subtitle'] = $subtitle;
	$variables['pagedata'] = $pagedata;
	$variables['pagenum'] = $item['pagenum'];
	if($variables['html']) {
		$variables['htmlfilename'] = FORE_ROOT.itemhtmlname($item['id'], 1, $item, $categorycache);
		if($item['category'] > 0) {
			for($i = 2; $i <= 4; $i ++) {
				$variables['htmlfilename'.$i] = '';
				$_itemhtmlname = itemhtmlname($item['id'], $i, $item, $categorycache);
				if($_itemhtmlname != '') $variables['htmlfilename'.$i] = FORE_ROOT.$_itemhtmlname;
				$variables['template'.$i] = $categorycache['template'.$i];
			}
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
	require_once(CORE_ROOT.'include/task.file.func.php');
	deletetask('indextask');
	foreach($ids as $id) {
		addtask('indextaskitem', "item\n".$id."\n\n0");
	}
	while($task = gettask('indextaskitem')) {
		list($type, $id, $filename, $page) = explode("\n", $task);
		if(strpos($filename, '?') !== false) continue;
		if($type != 'item') continue;
		$variables = get_item_data($id);
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
				$result = render_template($variables, '', 1);
				if($result === false) return false;
				for($i = 2; $i <= 4; $i ++) {
					if(empty($variables['template'.$i]) || empty($variables['htmlfilename'.$i])) continue;
					$variables['template'] = $variables['template'.$i];
					$variables['htmlfilename'] = $variables['htmlfilename'.$i];
					render_template($variables, '', 1);
				}
			}
			if(empty($params['nopage'])) {
				if($variables['pagenum'] > 0) {
					for($i = 1; $i <= $variables['pagenum']; $i ++) {
						$textdata = get_text_data($id, $i, $variables, $cc);
						unset($textdata['id']);
						$textdata += $variables;
						render_template($textdata, $cc['pagetemplate'], 1);
					}
				}
			}
		}
		unset($GLOBALS['index_work']);
	}
	unset($GLOBALS['batchcreateitemflag']);
}

function getkeywordscache() {
	global $codekey;
	$_keywords = array();
	if($fp = @fopen(AK_ROOT.'configs/keywords.txt', 'r')) {
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
	$filename = $item['filename'];
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
		$storemethod = empty($cc) ? '' : $cc['storemethod'];
	} else {
		$storemethod = empty($cc) ? '' : $cc['storemethod'.$version];
	}
	$path = str_replace('[categorypath]', $fullpath, $storemethod);
	$path = str_replace('[path]', $_path, $path);
	if(isset($item['title'])) $path = str_replace('[title]', $item['title'], $path);
	$path = str_replace('[y]', $year, $path);
	$path = str_replace('[m]', $month, $path);
	$path = str_replace('[d]', $day, $path);
	$path = str_replace('[id]', $id, $path);
	#$path = preg_replace('/\[id\/([\d]+)\]/e', "ceil($id/\\1)", $path);
	$path = preg_replace_callback('/\[id\/([\d]+)\]/', function ($matches){
		return "[id/" . ceil($id/$matches[1]) . "]";}, $path);
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
	global $db;
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

function changeuserpassword($uid, $password) {
	global $db;
	$db->update('users', array('password' => ak_md5($password, 1, 2)), "id='$uid'");
}

function deleteuser($uid) {
	global $db;
	$db->delete('users', "id='$uid'");
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
		if(!empty($task)) {
			require_once(CORE_ROOT.'include/task.file.func.php');
			addtask('spiderpicture', $pictureurl."\t".FORE_ROOT.$picname);
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
		//$filename = random(6).'.'.fileext($filename);
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
			if(!a_is_int($newid)) continue;
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
?>
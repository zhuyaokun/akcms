<?php
if(!defined('CORE_ROOT')) exit;
if(empty($cachetype)) $cachetype = 'file';
require_once(CORE_ROOT.'include/cache.'.$cachetype.'.func.php');
function updatecache($cachename = '', $designate = array()) {
	global $db, $tablepre, $settings, $homepage;
	if(empty($db)) $db = db();
	if(empty($cachename) || $cachename == 'modules') {
		$query = $db->query_by('*', 'modules', '1', 'id');
		$return = array();
		while($module = $db->fetch_array($query)) {
			$module['data'] = ak_unserialize($module['data']);
			$return[$module['id']] = $module;
		}
		$_query = $db->query_by('id,category,module', 'categories');
		while($_category = $db->fetch_array($_query)) {
			$moduleid = $_category['module'];
			if($moduleid <= 0) $moduleid = 1;
			if(!isset($return[$moduleid])) continue;
			if(!isset($return[$moduleid]['categories'])) $return[$moduleid]['categories'] = array();
			$return[$moduleid]['categories'][$_category['id']] = $_category['category'];
		}
		setcache('modules', $return);
	}
	if(empty($cachename) || $cachename == 'settings') {
		$return = array();
		$sql = "SELECT * FROM {$tablepre}_settings";
		$query = $db->query($sql);
		while($var = $db->fetch_array($query)) {
			$return[$var['variable']] = $var['value'];
		}
		$settings = $return;
		setcache('settings', $return);
	}
	if(empty($cachename) || substr($cachename, 0, 8) == 'category') {
		$return = array();
		if(empty($cachename) || strlen($cachename) == 8) {
			$query = $db->query_by('categoryup,id', 'categories', 'categoryup>=0');
			$subcategories = array();
			while($category = $db->fetch_array($query)) {
				$subcategories[$category['categoryup']][] = $category['id'];
			}
			$cs = array(0);
			for($i = 0; $i < count($cs); $i ++) {
				$c = $cs[$i];
				if($c > 0) {
					$category = $db->get_by('*', 'categories', "id='$c'");
					$category['subcategories'] = array();
					if(isset($subcategories[$c])) $category['subcategories'] = $subcategories[$c];
					$extvalaue = updatecategoryextvalue($c);
					$category = array_merge($category, $extvalaue);
					setcache('category-'.$c, $category);
				}
				if(!isset($subcategories[$c])) continue;
				foreach($subcategories[$c] as $_c) {
					array_push($cs, $_c);
				}
			}
			unset($subcategories);
		} else {
			$settings = getcache('settings');
			$id = substr($cachename, 8);
			if(!a_is_int($id)) return false;
			if(!$category = $db->get_by('*', 'categories', "id='$id'")) return false;
			$query = $db->query_by('id', 'categories', "categoryup='$id'");
			while($subcategory = $db->fetch_array($query)) {
				$category['subcategories'][] = $subcategory['id'];
			}
			$extvalaue = updatecategoryextvalue($id);
			$category = array_merge($category, $extvalaue);
			setcache('category-'.$id, $category);
		}
		unset($return, $extvalaue, $category, $subcategory);
		updatecache('categoriesselect');
		deletecache('categorytree');
	}
	if(empty($cachename) || $cachename == 'categoriesselect') {
		$select = rendercategoryselect();
		setcache('categoriesselect', $select);
	}
	if(empty($cachename) || $cachename == 'sections') {
		$return = array();
		$sql = "SELECT * FROM {$tablepre}_sections";
		$query = $db->query($sql);
		while($var = $db->fetch_array($query)) {
			$return[$var['id']] = $var;
		}
		setcache('sections', $return);
	}
	if(empty($cachename) || $cachename == 'templates') {
		global $template_path;
		$return = array();
		$dir = AK_ROOT.'configs/templates/'.$template_path.'/';
		if($dh = opendir($dir)) {
			while($filename = readdir($dh)) {
				if($filename != '.' && $filename != '..' && substr($filename, 0, 1) == ',') {
					$return[] = substr($filename, 1);
				}
			}
			sort($return);
			setcache('templates', $return);
		}
	}
	if(empty($cachename) || $cachename == 'globalvariables') {
		$return = array();
		$sql = "SELECT * FROM {$tablepre}_variables ORDER BY variable";
		$query = $db->query($sql);
		$vars = array();
		while($var = $db->fetch_array($query)) {
			$vars[$var['variable']] = $var;
		}
		foreach($vars as $var) {
			$v = $var['value'];
			preg_match_all('/\{\$([a-zA-Z0-9_]+?)\}/is', $v, $matches);
			foreach($matches[1] as $m) {
				$r = '';
				if(strpos($m, 'v_') === 0) {
					if(isset($vars[substr($m, 2)])) $r = $vars[substr($m, 2)]['value'];
				} else {
					if(in_array($m, array('homepage')) && isset($GLOBALS[$m])) $r = $GLOBALS[$m];
				}
				$v = str_replace('{$'.$m.'}', $r, $v);
			}
			if($var['type'] == 'picture') $v = pictureurl($v);
			$return[$var['variable']] = $v;
		}
		setcache('globalvariables', $return);
	}
	if((empty($cachename) || $cachename == 'infos')) {
		$return = array();
		$items = $db->get_by('COUNT(*)', 'items', 'category>0');
		$pvs1 = $db->get_by('SUM(pageview)', 'items');
		$pvs2 = $db->get_by('SUM(pv)', 'categories');
		$editors = $db->get_field("SELECT COUNT(*) FROM {$tablepre}_admins WHERE freeze=0");
		$attachmentsizes = $db->get_field("SELECT SUM(filesize) FROM {$tablepre}_attachments");
		$attachments = $db->get_field("SELECT COUNT(*) FROM {$tablepre}_attachments");
		$return = array(
			'items' => $items,
			'pvs' => $pvs1 + $pvs2,
			'editors' => $editors,
			'attachmentsizes' => $attachmentsizes,
			'attachments' => $attachments
		);
		setcache('infos', $return);
	}
	if(empty($cachename) || $cachename == 'apps') {
		$return = array();
		$apps = readpathtoarray(AK_ROOT.'configs/apps/', 1);
		foreach($apps as $app) {
			if(is_dir(AK_ROOT.'configs/apps/'.$app.'/templateplugin')) {
				$tps = readpathtoarray(AK_ROOT.'configs/apps/'.$app.'/templateplugin', 1);
				foreach($tps as $tp) {
					if(strlen($tp) < 5) continue;
					if(substr($tp, -4) !== '.php') continue;
					if(!preg_match('/^[a-zA-Z][a-zA-Z0-9_]+\.php$/i', $tp)) continue;
					$return[] = substr($tp, 0, -4);
				}
			}
		}
		setcache('templateplugins', $return);
		require_once(CORE_ROOT.'include/app.func.php');
		scanapps();
	}
	if(empty($cachename) || $cachename == 'filters') {
		$return = array();
		$sql = "SELECT * FROM {$tablepre}_filters";
		$query = $db->query($sql);
		while($f = $db->fetch_array($query)) {
			$return[$f['id']] = $f['data'];
			if(!empty($f['title'])) $return[$f['title']] = $f['data'];
		}
		setcache('filters', $return);
	}
}

function getcategorycache($id) {
	return getcache('category-'.$id);
}
?>
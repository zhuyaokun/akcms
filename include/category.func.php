<?php
if(!defined('CORE_ROOT')) exit;
function get_category_data($id, $template = 'default') {
	global $db, $lan, $thetime, $setting_ifhtml;
	$variables = array();
	$variables['_pagetype'] = 'category';
	if(!a_is_int($id)) return false;
	$variables['_pageid'] = $id;
	$categorycache = getcategorycache($id);
	if(!$category = $db->get_by('*', 'categories', "id='{$id}'")) return array();
	$variables['defaulttemplate'] = $categorycache['defaulttemplate'];
	$variables['template'] = $categorycache['defaulttemplate'];
	$variables['listtemplate'] = $categorycache['listtemplate'];
	if(!empty($categorycache['subcategories'])) {
		$variables['subcategories'] = $categorycache['subcategories'];
	} else {
		$variables['subcategories'] = array();
	}
	$variables['category'] = $category['id'];
	$variables['alias'] = $category['alias'];
	$variables['categoryname'] = $category['category'];
	$variables['path'] = $category['path'];
	$variables['data'] = $category['data'];
	$variables['categoryup'] = $category['categoryup'];
	$variables['orderby'] = $category['orderby'];
	$variables['keywords'] = $category['keywords'];
	$variables['description'] = $variables['categorydescription'] = $category['description'];
	$variables['items'] = $category['items'];
	$variables['allitems'] = $category['allitems'];
	$variables['pv'] = $category['pv'];
	if($category['html'] == 0) {
		$variables['html'] = $setting_ifhtml;
	} else {
		$variables['html'] = $category['html'];
	}
	$variables['storemethod'] = $category['storemethod'];
	$total = $db->get_by('COUNT(*) as c', 'items', "category='$id'");
	$variables['total'] = $total;
	$path = $categorycache['fullpath'];
	$_homemethod = FORE_ROOT.$categorycache['categoryhomemethod'];
	$_homemethod = str_replace('[categorypath]', $path, $_homemethod);
	$variables['htmlfilename'] = $_homemethod;
	$variables['url'] = getcategoryurl($id);
	$variables['pagetemplate'] = $categorycache['itemtemplate'];
	$variables['categoryhomemethod'] = $categorycache['categoryhomemethod'];
	$variables['categorypagemethod'] = $categorycache['categorypagemethod'];
	$variables['picture'] = $categorycache['picture'];
	return $variables;
}

function batchcategoryhtml($ids) {
	if(!is_array($ids)) $ids = array($ids);
	foreach($ids as $id) {
		$variables = get_category_data($id);
		$GLOBALS['index_work'] = "category\n".$id."\n".$variables['htmlfilename'];
		render_template($variables, '', 1);
	}
}

function operatecreatecategoryprocess() {
	require_once(CORE_ROOT.'include/task.file.func.php');
	unset($GLOBALS['index_work']);
	$tasks = gettask('indextaskcategory', 50);
	if(empty($tasks)) return true;
	foreach($tasks as $task) {
		list($type, $id, $filename, $page) = explode("\n", $task);
		$variables = get_category_data($id);
		if($page > 1) $variables['template'] = $variables['listtemplate'];
		$variables['page'] = $page;
		$variables['htmlfilename'] = $filename;
		render_template($variables, '', 1);
	}
}

function get_category_homemethod($id) {
	global $setting_categoryhomemethod;
	$categorycache = getcategorycache($id);
	$categoryhomemethod = $categorycache['categoryhomemethod'];
	if($categoryhomemethod == '') $categoryhomemethod = $setting_categoryhomemethod;
	return $categoryhomemethod;
}

function get_category_path($id) {
	if($id == 0) return '';
	$categorycache = getcategorycache($id);
	$path = $categorycache['fullpath'];
	return $path;
}

function getcategoryurl($id, $categorycache = array()) {
	global $homepage;
	if($id == 0) return '';
	if(empty($categorycache)) $categorycache = getcategorycache($id);
	$url = $categorycache['categoryhomemethod'];
	$url = str_replace('[id]', $id, $url);
	$url = str_replace('[categorypath]', $categorycache['fullpath'], $url);
	$url = str_replace('[path]', $categorycache['path'], $url);
	$url = pictureurl($url);
	return $url;
}

function includesubcategories($stringcategories) {
	$stringcategories = tidyitemlist($stringcategories, ',', 0);
	$outputcategories = explode(',',$stringcategories);
	$outputcategories = array_unique($outputcategories);
	for($i = 0; $i < count($outputcategories); $i ++) {
		$id = $outputcategories[$i];
		$categorycache = getcategorycache($id);
		if(!empty($categorycache['subcategories'])) {
			$outputcategories = array_merge($outputcategories, $categorycache['subcategories']);
		}
	}
	return implode(',', $outputcategories);
}

function getidbyfullpath($path) {
	$paths = explode('/', $path);
	$paths = array_reverse($paths);
	foreach($paths as $path) {
		$where = "path='$path'";
		if(a_is_int($path)) $where = "(id='$path' AND path='') OR ";
		$query = $db->query_by('id,path,category,categoryup', 'categories', $where);
	}
}

function rendercategorybranch($id, $branches, $subcategories) {
	$return = '';
	if($id > 0) {
		$return = "<div class='ci'>";
		if(!empty($subcategories[$id])) {
			$return .= "<div id='f$id' class='f2'></div><div class='i'><a href='javascript:i($id)' class='w9'></a></div><span class='w20'></span>";
		} else {
			$return .= "<div class='f'></div>";
		}
		$return .= ("<div class='yzline'>".$branches[$id]."</div>");
	}
	if(!empty($subcategories[$id])) {
		$return .= "<div id='c$id'>";
		foreach($subcategories[$id] as $c) {
			$return .= rendercategorybranch($c, $branches, $subcategories);
		}
		$return .="</div>";
	}	
	if($id > 0) $return .= "</div>";
	return $return;
}

function rendercategorytree() {
	global $db, $lan;
	$cachekey = 'categorytree';
	if($tree = getcache($cachekey)) return $tree;
	$query = $db->query_by('*', 'categories', 'categoryup>=0');
	$subcategories = $branches = $categories = array();
	while($category = $db->fetch_array($query)) {
		$categories[] = $category;
		$subcategories[$category['categoryup']][] = $category['id'];
		$branches[$category['id']] = rendercategoryleaf($category);
	}
	$tree = rendercategorybranch(0, $branches, $subcategories);
	$tree = "<div class='treeroot'></div>{$lan['allcategory']}[<a href='index.php?file=admincp&action=newcategory&parent=0'>{$lan['addsubcategory']}</a>]".$tree;
	unset($branches, $key, $keys, $categories, $id, $subcategories);
	if(strlen($tree) > 102400) setcache($cachekey, $tree);
	return $tree;
}

function rendercategoryleaf($category) {
	global $lan;
	$id = $category['id'];
	//$branch = "<div class=categoryid>{$id}. </div><div class=categoryname><a href='?action=editcategory&id={$id}'>{$category['category']}</a></div><div class=categoryopt><a href='?action=newcategory&parent={$id}'>[+{$lan['subcategory']}]</a> <a href='?action=newitem&category={$id}'>[+{$lan['item']}]</a> <a href='?action=items&category={$id}'>[{$lan['item']}]</a> <a href='javascript:d({$id})'>[{$lan['del']}]</a></div>";	
	$branch = "{$id}. <a href='?action=editcategory&id={$id}'>{$category['category']}</a> [<a href='?action=newcategory&parent={$id}'>+{$lan['subcategory']}</a>] [<a href='?action=newitem&category={$id}'>+{$lan['item']}</a>] [<a href='?action=items&category={$id}'>{$lan['item']}</a>] [<a href='javascript:d({$id})'>{$lan['del']}</a>]";
	if($category['items'] > 0) $branch .= " ({$category['items']})";
	return $branch;
}

function rendercategoryselect($id = 0, $layer = 0) {
	global $db, $categories;
	if(!empty($GLOBALS['nocategoryselect'])) return '';
	$cachekey = 'categoryselect';
	if($id == 0 && $categoryselect = getcache($cachekey)) return $categoryselect;
	$_tree = '';
	$_sub = '';
	static $categories;
	if(empty($categories)) {
		$query = $db->query_by('id,category,categoryup,items', 'categories', '', 'categoryup,id');
		while($category = $db->fetch_array($query)) {
			$categories[$category['id']] = $category;
			$categories[$category['categoryup']]['subcategories'][] = $category['id'];
		}
	}
	if(!empty($categories[$id]['subcategories']) && is_array($categories[$id]['subcategories'])) {
		foreach($categories[$id]['subcategories'] as $category) {
			$_sub .= rendercategoryselect($category, $layer + 1);
		}
	}
	if($id > 0) {
		$_tree .= "<option value=\"$id\">".str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $layer).ak_htmlspecialchars($categories[$id]['category'])."</option>\n".$_sub;
	} else {
		$_tree .= $_sub;
	}
	if($id == 0) setcache($cachekey, $_tree);
	return $_tree;
}

function ifhassubcategories($id) {
	$categorycache = getcategorycache($id);
	if(empty($categorycache['subcategories'])) return false;
}

function updatecategoryextvalue($id, $category = array()) {
	global $db, $settings_ifhtml, $settings;
	$fullpath = '';
	if(empty($category)) $category = $db->get_by('*', 'categories', "id='$id'");
	$modules = getcache('modules');
	if(!isset($modules[$category['module']])) return array();
	$moduleext = $modules[$category['module']]['data'];
	$fields = array('storemethod', 'categoryhomemethod', 'categorypagemethod', 'defaulttemplate', 'listtemplate', 'itemtemplate', 'html', 'pagetemplate', 'pagestoremethod', 'template2', 'template3', 'template4', 'storemethod2', 'storemethod3', 'storemethod4');
	if($category['categoryup'] > 0) {
		$categoryupcache = getcategorycache($category['categoryup']);
		$fullpath = $categoryupcache['fullpath'];
	}
	$settings['defaulttemplate'] = 'category_home.htm';
	$settings['listtemplate'] = 'category_list.htm';
	$settings['itemtemplate'] = 'item_display.htm';
	$settings['html'] = $settings_ifhtml;
	$extvalue = array();
	foreach($fields as $f) {
		$extvalue[$f] = '';
		if(isset($settings[$f])) $extvalue[$f] = $settings[$f];
		if(!empty($moduleext[$f])) {$extvalue[$f] = $moduleext[$f];}
		if(!empty($categoryupcache[$f])) {$extvalue[$f] = $categoryupcache[$f];}
		if(!empty($category[$f])) {$extvalue[$f] = $category[$f];}
	}
	$path = $id;
	if($category['path'] != '') $path = $category['path'];
	$fullpath .= '/'.$path;
	if(substr($fullpath, 0, 1) == '/') $fullpath = substr($fullpath, 1);
	$extvalue['path'] = $path;
	$extvalue['fullpath'] = $fullpath;
	$category = array();
	$category['value'] = serialize($extvalue);
	$db->update('categories', $category, "id='$id'");
	return $extvalue;
}

function updatecategoryfilename($id) {
	global $thetime, $db;
	$filename = get_category_path($id);
	if($db->get_by('filename,id,htmlid', 'filenames', "id='$id' AND type='category'")) {
		$db->update('filenames', array('filename' => $filename, 'dateline' => $thetime), "id='$id' AND type='category'");
	} else {
		$db->insert('filenames', array('filename' => $filename, 'dateline' => $thetime, 'id' => $id, 'type' => 'category'));
	}
}

function attachcategoryextvalue($category) {
	if(!empty($category['value']) && $value = @unserialize($category['value'])) {
		unset($category['value']);
		$category = array_merge($category, $value);
	}
	return $category;
}
?>
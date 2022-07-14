<?php
if(!defined('CORE_ROOT')) @include 'include/directaccess.php';
require CORE_ROOT.'include/admin.inc.php';
if(!isset($get_action) || $get_action == 'custom' || $get_action == 'admin') {
	if(!isset($get_action)) {
		$get_action = 'admin';
		if(ifcustomed()) $get_action = 'custom';
	}
	if($get_action == 'custom') {
		$customlan = loadlan(AK_ROOT.'configs/language/menu.php');
		$lan = array_merge($lan, $customlan);
		$menudata = getmenus('custom');
		$usermode = 'editor';
	} else {
		$menudata = getmenus();
		$usermode = '';
	}
	$groups = $menudata['groups'];
	
	$favorite = "<b>{$groups['favorite']['title']}</b>";
	foreach($groups['favorite']['menus'] as $menu) {
		$favorite .= rendermenulink($menu);
	}
	unset($groups['favorite']);
	
	$createhtml = "<b>{$groups['createhtml']['title']}</b>";
	foreach($groups['createhtml']['menus'] as $menu) {
		$createhtml .= rendermenulink($menu);
	}
	unset($groups['createhtml']);
	
	$webeditor = "<b>{$groups['webeditor']['title']}</b>";
	foreach($groups['webeditor']['menus'] as $menu) {
		$webeditor .= rendermenulink($menu);
	}
	unset($groups['webeditor']);
	
	$account = "<b>{$groups['account']['title']}</b>";
	foreach($groups['account']['menus'] as $menu) {
		$account .= rendermenulink($menu);
	}
	
	$softhomepage = 'http://www.akhtm.com/?source=cplogo';
	$logo = 'images/admin/logo.gif';
	if(file_exists(AK_ROOT.'configs/images/logo.gif')) $logo = 'configs/images/logo.gif';
	if(isset($menudata['homepage'])) $softhomepage = $menudata['homepage'];
	$customed = 0;
	if(ifcustomed()) $customed = 1;
	$menu = rendermenu($groups);
	$nav = rendernav($groups);
	$smarty->assign('menu', $menu);
	$menuwidth = 80;
	if(!empty($setting_menuwidth)) $menuwidth = $setting_menuwidth;
	$w2 = $menuwidth + 4;
	$smarty->assign('menuwidth', $menuwidth);
	$smarty->assign('menuwidth2', $w2);
	$smarty->assign('nav', $nav);
	$smarty->assign('customed', $customed);
	$smarty->assign('usermode', $usermode);
	$smarty->assign('softhomepage', $softhomepage);
	$smarty->assign('logo', $logo);
	$smarty->assign('favorite', $favorite);
	$smarty->assign('webeditor', $webeditor);
	$smarty->assign('createhtml', $createhtml);
	$smarty->assign('account', $account);
	displaytemplate('layout.htm');
	aexit();
} elseif($get_action == 'categories') {
	checkcreator();
	$smarty->assign('categoriestree', rendercategorytree());
	displaytemplate('admincp_categories.htm');
} elseif($get_action == 'newcategory') {
	checkcreator();
	if(!empty($_POST)) {
		if(empty($post_category)) adminmsg($lan['nocategoryname'], 'back', 3, 1);
		$values = array(
			'categoryup' => $post_categoryup,
			'category' => $post_category,
			'module' => 1
		);		
		$db->insert('categories', $values);
		$categoryid = $db->insert_id();
		updatecache('category'.$categoryid);
		deletecache('categorytree');
		deletecache('categoryselect');
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=editcategory&id='.$categoryid);
	} else {
		if(empty($get_parent)) $get_parent = 0;
		$smarty->assign('parent', $get_parent);
		displaytemplate('admincp_category_new.htm');
	}
} elseif($get_action == 'deletecategory') {
	checkcreator();
	vc();
	if(!isset($get_id) || !a_is_int($get_id)) adminmsg($lan['parameterwrong'], 'back', 3, 1);
	$item = $db->get_by('*', 'items', "category='$get_id'");
	if($item !== false) adminmsg($lan['delcategoryhasitem'], 'back', 3, 1);
	$category = $db->get_by('*', 'categories', "categoryup='$get_id'");
	if($category !== false) adminmsg($lan['delcategoryhassub'], 'back', 3, 1);
	$db->delete('categories', "id='$get_id'");
	deletecache('category'.$get_id);
	deletecache('categorytree');
	deletecache('categoryselect');
	adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=categories');
} elseif($get_action == 'editcategory') {
	checkcreator();
	if(!isset($get_id) || !a_is_int($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
	$id = $get_id;
	if(empty($_POST)) {
		$category = $db->get_by('*', 'categories', "id='$id'");
		if($category == false) adminmsg($lan['parameterwrong'], '', 3, 1);
		$selectcategories = get_select('category');
		$selectmodules = get_select('modules');
		$selecttemplates = get_select_templates();
		$smarty->assign('selecttemplates', $selecttemplates);
		$smarty->assign('selectmodules', $selectmodules);
		$smarty->assign('selectcategories', $selectcategories);
		$smarty->assign('id', $id);
		$smarty->assign('category', ak_htmlspecialchars($category['category']));
		$smarty->assign('module', $category['module']);
		$smarty->assign('alias', ak_htmlspecialchars($category['alias']));
		$smarty->assign('orderby', $category['orderby']);
		$smarty->assign('path', $category['path']);
		$smarty->assign('domain', $category['domain']);
		$smarty->assign('categoryup', $category['categoryup']);
		$smarty->assign('html', $category['html']);
		$smarty->assign('usefilename', $category['usefilename']);
		$smarty->assign('itemtemplate', $category['itemtemplate']);
		$smarty->assign('defaulttemplate', $category['defaulttemplate']);
		$smarty->assign('listtemplate', $category['listtemplate']);
		$smarty->assign('storemethod', $category['storemethod']);
		$smarty->assign('storemethod2', $category['storemethod2']);
		$smarty->assign('storemethod3', $category['storemethod3']);
		$smarty->assign('storemethod4', $category['storemethod4']);
		$smarty->assign('template2', $category['template2']);
		$smarty->assign('template3', $category['template3']);
		$smarty->assign('template4', $category['template4']);
		$smarty->assign('pagetemplate', $category['pagetemplate']);
		$smarty->assign('pagestoremethod', $category['pagestoremethod']);
		$smarty->assign('categoryhomemethod', $category['categoryhomemethod']);
		$smarty->assign('categorypagemethod', $category['categorypagemethod']);
		$smarty->assign('description', ak_htmlspecialchars($category['description']));
		$smarty->assign('keywords', ak_htmlspecialchars($category['keywords']));
		$smarty->assign('data', ak_htmlspecialchars($category['data']));
		$smarty->assign('picture', $category['picture']);
		$smarty->assign('repicture', pictureurl($category['picture']));
		displaytemplate('admincp_category_edit.htm');
	} else {
		if(empty($post_category)) adminmsg($lan['nocategoryname'], 'back', 3, 1);
		if(!a_is_int($post_order)) $post_order = 0;
		if($get_id == $post_categoryup) adminmsg($lan['upperisself'], 'back', 3, 1);
		$category = $db->get_by('*', 'categories', "id='$id'");
		if($category['path'] != $post_path) {
			$pathchecked = checkcategorypath($post_path, $post_categoryup);
			if($pathchecked != '') adminmsg($pathchecked, 'back', 3, 1);
		}
		if(!empty($post_domain)) {
			$_c = $db->get_by('*', 'categories', "domain='$post_domain'");
			if($_c['id'] != $get_id) adminmsg('error', 'back', 3, 1);
		}
		
		if(!empty($file_uploadpicture['name'])) {
			$headpicext = fileext($file_uploadpicture['name']);
			if(!ispicture($file_uploadpicture['name'])) adminmsg($lan['pictureexterror'], 'back', 3, 1);
			$filename = get_upload_filename($file_uploadpicture['name'], 0, $post_category, 'preview');
			if(uploadfile($file_uploadpicture['tmp_name'], FORE_ROOT.$filename)) $picture = $filename;
			ak_ftpput(FORE_ROOT.$filename, $filename);
		} elseif(isset($post_data_pickpicture)) {
			$picture = pickpicture($post_data, $homepage);
		} else {
			$picture = isset($post_picture) ? $post_picture : false;
		}
		$value = array(
			'categoryup' => $post_categoryup,
			'category' => $post_category,
			'module' => $post_module,
			'alias' => $post_alias,
			'orderby' => $post_order,
			'description' => $post_description,
			'keywords' => $post_keywords,
			'path' => $post_path,
			'itemtemplate' => $post_itemtemplate,
			'defaulttemplate' => $post_defaulttemplate,
			'listtemplate' => $post_listtemplate,
			'html' => $post_html,
			'storemethod' => $post_storemethod,
			'categorypagemethod' => $post_categorypagemethod,
			'categoryhomemethod' => $post_categoryhomemethod,
			'storemethod2' => $post_storemethod2,
			'storemethod3' => $post_storemethod3,
			'storemethod4' => $post_storemethod4,
			'pagetemplate' => $post_pagetemplate,
			'pagestoremethod' => $post_pagestoremethod,
			'template2' => $post_template2,
			'template3' => $post_template3,
			'template4' => $post_template4,
			'data' => $post_data,
			'picture' => $picture,
		);
		if(isset($post_domain)) $value['domain'] = $post_domain;
		$db->update('categories', $value, "id='$get_id'");
		updatecache('category'.$get_id);
		updatecategoryfilename($get_id);
		updatecache('modules');
		if($category['module'] != $post_module) updatecache('modules');
		deletecache('categorytree');
		deletecache('categoryselect');
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=categories');
	}
} elseif($get_action == 'sections') {
	checkcreator();
	$query = $db->query_by('*', 'sections', '', 'id');
	$str_sections = '';
	while($section = $db->fetch_array($query)) {
		$str_sections .= "<tr><td>{$section['id']}</td><td><a href=\"index.php?file=admincp&action=editsection&id={$section['id']}\">{$section['section']}</a></td><td>{$section['description']}</td><td>{$section['items']}</td><td align=\"center\"><a href=\"index.php?file=admincp&action=createsection&id={$section['id']}\">{$lan['createdefault']}</a></td><td>{$section['orderby']}</td><td align='center'>".
		($section['id'] != 1 ? "<a href=\"javascript:deletesection({$section['id']})\">".alert($lan['delete'])."</a>" : $lan['delete'])."</td></tr>\r\n";
	}
	if($str_sections == '') $str_sections = '<tr><td colspan="10">'.$lan['section_no'].'</td></tr>';
	$selecttemplates = get_select_templates();
	$smarty->assign('str_sections', $str_sections);
	$smarty->assign('selecttemplates', $selecttemplates);
	$smarty->assign('setting_sectionhomemethod', ak_htmlspecialchars($setting_sectionhomemethod));
	$smarty->assign('setting_sectionpagemethod', ak_htmlspecialchars($setting_sectionpagemethod));
	displaytemplate('admincp_sections.htm');
} elseif($get_action == 'newsection') {
	checkcreator();
	if(empty($post_section)) adminmsg($lan['nosectionname'], 'back', 3, 1);
	if(!a_is_int($post_order)) $post_order = 0;
	$value = array(
		'section' => $post_section,
		'alias' => $post_alias,
		'orderby' => $post_order,
		'description' => $post_description,
		'keywords' => $post_keywords,
		'sectionpagemethod' => $post_sectionpagemethod,
		'sectionhomemethod' => $post_sectionhomemethod,
		'html' => $post_html,
		'listtemplate' => $post_listtemplate,
		'defaulttemplate' => $post_defaulttemplate,
	);
	$db->insert('sections', $value);
	updatecache('sections');
	adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=sections');
} elseif($get_action == 'deletesection') {
	checkcreator();
	vc();
	if(!isset($get_id) || !a_is_int($get_id)) adminmsg($lan['parameterwrong'], 'index.php?file=admincp&action=sections', 3, 1);
	if(intval($get_id) == 1) adminmsg($lan['defaultsectionnodel'], 'index.php?file=admincp&action=sections', 3, 1);
	$db->delete('sections', "id='$get_id'");
	updatecache('sections');
	adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=sections');
} elseif($get_action == 'editsection') {
	checkcreator();
	if(!isset($get_id) || !a_is_int($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
	if(!isset($post_section_edit_submit)) {
		$section = $db->get_by('*', 'sections', "id='$get_id'");
		if(empty($section)) adminmsg($lan['nosection'], '', 0, 1);
		$selecttemplates = get_select_templates();
		$smarty->assign('selecttemplates', $selecttemplates);
		$smarty->assign('id', $get_id);
		$smarty->assign('section', ak_htmlspecialchars($section['section']));
		$smarty->assign('alias', ak_htmlspecialchars($section['alias']));
		$smarty->assign('orderby', $section['orderby']);
		$smarty->assign('description', ak_htmlspecialchars($section['description']));
		$smarty->assign('keywords', ak_htmlspecialchars($section['keywords']));
		$smarty->assign('sectionhomemethod', ak_htmlspecialchars($section['sectionhomemethod']));
		$smarty->assign('sectionpagemethod', ak_htmlspecialchars($section['sectionpagemethod']));
		$smarty->assign('defaulttemplate', ak_htmlspecialchars($section['defaulttemplate']));
		$smarty->assign('listtemplate', ak_htmlspecialchars($section['listtemplate']));
		$smarty->assign('html', $section['html']);
		$smarty->assign('setting_sectionhomemethod', ak_htmlspecialchars($setting_sectionhomemethod));
		$smarty->assign('setting_sectionpagemethod', ak_htmlspecialchars($setting_sectionpagemethod));
		displaytemplate('admincp_section_edit.htm');
	} else {
		if(empty($post_section)) adminmsg($lan['nosectionname'], 'back', 0, 1);
		if(!a_is_int($post_order)) $post_order = 0;
		$value = array(
			'section' => $post_section,
			'alias' => $post_alias,
			'orderby' => $post_order,
			'description' => $post_description,
			'keywords' => $post_keywords,
			'sectionpagemethod' => $post_sectionpagemethod,
			'sectionhomemethod' => $post_sectionhomemethod,
			'html' => $post_html,
			'listtemplate' => $post_listtemplate,
			'defaulttemplate' => $post_defaulttemplate,
		);
		$db->update('sections', $value, "id='$get_id'");
		updatecache('sections');
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=sections');
	}
} elseif($get_action == 'newitem') {
	$moduleid = 1;
	$item = array();
	if(isset($get_category)) {
		$category = $get_category;
		$item['category'] = $category;
		$categoryvalue = getcategorycache($category);
		if(!empty($categoryvalue['module'])) $moduleid = $categoryvalue['module'];
	} else {
		if(!empty($get_module)) $moduleid = $get_module;
		if(akgetcookie('lastcategory') != '') {
			$categoryvalue = getcategorycache(akgetcookie('lastcategory'));
			if($categoryvalue['module'] == $moduleid) $item['category'] = akgetcookie('lastcategory');
		}
	}
	aksetcookie('lastmoduleid', $moduleid);
	$_c = empty($item['category']) ? 0 : $item['category'];
	$categorylist = '';
	while($_c != 0) {
		$categorylist = $_c.','.$categorylist;
		$_categories[$_c] = getcategorycache($_c);
		$_c = $_categories[$_c]['categoryup'];
	}
	$categorylist = substr($categorylist, 0, -1);
	$item['id'] = $thetime;
	$htmlfields = renderitemfield($moduleid, $item);
	$drafttemplate = "";
	if(!empty($setting_ifdraft)) $drafttemplate="<input id='draft' name='drafts' type='button' value='{$lan['savetodraft']}'>";
	if(empty($htmlfields)) adminmsg($lan['nocategorybinded'], 'index.php?file=admincp&action=categories', 3, 1);
	$smarty->assign('drafttemplate', $drafttemplate);
	$smarty->assign('moduleid', $moduleid);
	$smarty->assign('operate', $lan['add']);
	$smarty->assign('categorylist', $categorylist);
	$smarty->assign('action', 'index.php?file=admincp&action=newitem');
	$smarty->assign('htmlfields', $htmlfields);
	displaytemplate('admincp_moduleitem.htm');
} elseif($get_action == 'edititem') {
	if(!isset($get_id) || !a_is_int($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
	$draft = "";
	if(!empty($setting_ifdraft)) $draft="<input id='draft' name='drafts' type='button' value='{$lan['savetodraft']}'>";
	$item = $db->get_by('*', 'items', "id='$get_id'");
	$_c = ($item['category']);
	$categorylist = '';
	while($_c != 0) {
		$categorylist = $_c.','.$categorylist;
		$_categories[$_c] = getcategorycache($_c);
		$_c = $_categories[$_c]['categoryup'];
	}
	$categorylist = substr($categorylist, 0, -1);
	if($item === false) adminmsg($lan['parameterwrong'], '', 3, 1);
	if(empty($item['category'])) go("index.php?file=admincp&action=specialpages&id={$get_id}");
	$extdata = $db->get_by('value', 'item_exts', "id='$get_id'");
	$itemext = @unserialize($extdata);
	if(!empty($itemext)) $item = array_merge($item, $itemext);
	$text = $db->get_by('text', 'texts', "itemid='$get_id' AND page=0");
	$item['data'] = $text;
	$category = getcategorycache($item['category']);
	$moduleid = $category['module'];
	aksetcookie('lastmoduleid', $moduleid);
	$htmlfields = renderitemfield($moduleid, $item);
	$smarty->assign('id', $get_id);
	$smarty->assign('drafttemplate', $draft);
	$smarty->assign('categorylist', $categorylist);
	$smarty->assign('moduleid', $moduleid);
	$smarty->assign('operate', $lan['edit']);
	$smarty->assign('htmlfields', $htmlfields);
	displaytemplate('admincp_moduleitem.htm');
} elseif($get_action == 'saveitem') {
	if(empty($post_title)) adminmsg($lan['notitle'], 'back', 3, 1);
	if(empty($post_category)) adminmsg($lan['nocategory'], 'back', 3, 1);
	!isset($post_shorttitle) && $post_shorttitle = '';
	!isset($post_filename) && $post_filename = '';
	!isset($post_orderby) && $post_orderby = 0;
	!isset($post_orderby2) && $post_orderby2 = 0;
	!isset($post_orderby3) && $post_orderby3 = 0;
	!isset($post_orderby4) && $post_orderby4 = 0;
	!isset($post_orderby5) && $post_orderby5 = 0;
	!isset($post_orderby6) && $post_orderby6 = 0;
	!isset($post_orderby7) && $post_orderby7 = 0;
	!isset($post_orderby8) && $post_orderby8 = 0;
	!isset($post_section) && $post_section = 1;
	!isset($post_source) && $post_source = '';
	!isset($post_template) && $post_template = '';
	!isset($post_titlecolor) && $post_titlecolor = '';
	!isset($post_titlestyle) && $post_titlestyle = '';
	!isset($post_aimurl) && $post_aimurl = '';
	!isset($file_attach['name']) && $file_attach['name'] = array();
	!isset($file_attach['tmp_name']) && $file_attach['tmp_name'] = array();
	!isset($post_keywords) && $post_keywords = '';
	//if($setting_language == 'chinese') $post_keywords = str_replace(' ', ',', $post_keywords);
	!isset($post_digest) && $post_digest = '';
	!isset($post_shorttitle) && $post_shorttitle = '';
	!isset($post_titlestyle) && $post_titlestyle = '';
	!isset($post_titlecolor) && $post_titlecolor = '';
	!isset($post_aimurl) && $post_aimurl = '';
	!isset($post_data) && $post_data = '';
	!isset($post_price) && $post_price = 0;
	aksetcookie('lastcategory', $post_category);
	$modules = getcache('modules');
	$cc = getcategorycache($post_category);
	if($setting_usefilename) {
		if(!empty($post_filename) && strpos($post_filename, '.') === false && substr($post_filename, strlen($setting_htmlexpand) * -1) != $setting_htmlexpand) $post_filename .= $setting_htmlexpand;
		$filenamechecked = checkfilename($post_filename);
		if($filenamechecked != '') adminmsg($filenamechecked, 'back', 3, 1);
		if(!empty($post_filename)) {
			$htmlfilename = itemhtmlname($post_id, 1, array('category' => $post_category, 'filename' => $post_filename, 'dateline' => $thetime), $cc);
			if($existfile = $db->get_by('*', 'filenames', "filename='$htmlfilename'")) {
				if($existfile['id'] != $post_id) {
					adminmsg($lan['fileexist'], 'back', 6, 1);
				}
			}
		}
	}
	$ext = 0;
	$extvalue = array();
	$module = $cc['module'];
	$config['itemurl'] = '';
	$config['category'] = $post_category;
	if(isset($post_data_copypic)) $post_data = copypicturetolocal($post_data, $config);
	$moduledata = $modules[$module]['data'];
	foreach($moduledata['fields'] as $_k => $_v) {
		if(substr($_k, 0, 1) != '_') continue;
		if(isset($_POST[$_k])) {
			$extvalue[$_k] = $_POST[$_k];
		}
	}
	if(!empty($extvalue)) {
		$ext = 1;
		$extvalue = serialize($extvalue);
	}
	$extinsertvalue = array('value' => $extvalue);

	if(!empty($file_uploadpicture['name'])) {
		$headpicext = fileext($file_uploadpicture['name']);
		if(!ispicture($file_uploadpicture['name'])) adminmsg($lan['pictureexterror'], 'back', 3, 1);
		$filename = get_upload_filename($file_uploadpicture['name'], 0, $post_category, 'preview');
		if(uploadfile($file_uploadpicture['tmp_name'], FORE_ROOT.$filename)) $picture = $filename;
		ak_ftpput(FORE_ROOT.$filename, $filename);
	} elseif(isset($post_data_pickpicture)) {
		$picture = pickpicture($post_data, $homepage);
	} else {
		$picture = isset($post_picture) ? $post_picture : false;
	}	
	if(!empty($post_dateline)) {
		$post_dateline = str_replace(' ', '-', $post_dateline);
		$post_dateline = str_replace(':', '-', $post_dateline);
		list($y, $m, $d, $h, $i, $s) = explode('-', $post_dateline);
		$dateline = mktime($h, $i, $s, $m, $d, $y);
	}
	$filenames = array();
	$attachnum = 0;
	foreach($file_attach['name'] as $id => $a) {
		if(!empty($a)) {
			$attachnum ++;
		}
	}
	$values = array(
		'title' => $post_title,
		'shorttitle' => $post_shorttitle,
		'category' => $post_category,
		'section' => $post_section,
		'source' => $post_source,
		'editor' => $admin_id,
		'orderby' => $post_orderby,
		'orderby2' => $post_orderby2,
		'orderby3' => $post_orderby3,
		'orderby4' => $post_orderby4,
		'orderby5' => $post_orderby5,
		'orderby6' => $post_orderby6,
		'orderby7' => $post_orderby7,
		'orderby8' => $post_orderby8,
		'dateline' => $thetime,
		'lastupdate' => $thetime,
		'template' => $post_template,
		'filename' => $post_filename,
		'keywords' => $post_keywords,
		'digest' => $post_digest,
		'titlecolor' => $post_titlecolor,
		'titlestyle' => $post_titlestyle,
		'aimurl' => $post_aimurl,
		'ext' => $ext,
		'attach' => $attachnum,
		'price' => $post_price
	);
	if(!empty($post_drafth)) $values['draft'] = 1;
	if(isset($post_pageview)) $values['pageview'] = $post_pageview;
	if(!empty($moduledata['fields']['title']['ifinitial'])) $values['initial'] = getinitial($post_title);
	if($picture !== false) $values['picture'] = $picture;
	if(isset($post_author)) $values['author'] = $post_author;
	$hookfunction = "hook_saveitem_$module";
	if(function_exists($hookfunction)) $values = $hookfunction($values, $post_data);
	$hookfunction = "hook_saveitemdata_$module";
	if(function_exists($hookfunction)) $post_data = $hookfunction($post_data, $values);
	if(empty($post_id)) {//new item
		$values['latesthtml'] = 0;
		$db->insert('items', $values);
		$itemid = $db->insert_id();
		if($setting_usefilename) {
			$filename = itemhtmlname($itemid, 1, $values, $cc);
			if(!empty($filename)) {
				$values = array(
					'id' => $itemid,
					'filename' => $filename,
					'dateline' => $thetime,
					'type' => 'item'
				);
				$db->insert('filenames', $values);
			}
		}
		if(!empty($post_data)) {
			$data = array(
				'text' => $post_data,
			);
			$data['itemid'] = $itemid;
			$data['page'] = 0;
			$db->insert('texts', $data);
		}
		refreshitemnum($post_category, 'category');
		refreshitemnum($post_section, 'section');
	} else {//edit item
		$item = $db->get_by('*', 'items', "id='$post_id'");
		$values['attach'] += $item['attach'];
		if(isset($dateline)) {
			$values['dateline'] = $dateline;
		} else {
			$values['dateline'] = $item['dateline'];
		}
		$db->update('items', $values, "id='$post_id'");
		$itemid = $post_id;
		if(empty($post_data)) {
			$db->delete('texts', "itemid='$post_id' AND page='0'");
		} else {
			$data = array(
				'text' => $post_data,
			);
			if($db->get_by('*', 'texts', "itemid='$post_id' AND page='0'")) {
				$db->update('texts', $data, "itemid='$post_id' AND page='0'");
			} else {
				$data['itemid'] = $post_id;
				$data['page'] = 0;
				$db->insert('texts', $data);
			}
		}
		if(!empty($picture) && $item['picture'] != $picture) {
			if(preg_match('/^headpic\//', $item['picture'])) {
				@unlink(FORE_ROOT.$item['picture']);
			}
		}
		if($item['filename'] != $post_filename || $post_category != $item['category']) {
			$cc2 = getcategorycache($item['category']);
			@unlink(FORE_ROOT.itemhtmlname($post_id, 1, $item, $cc2));
		}
		if($setting_usefilename) {
			if(!empty($post_filename)) {
				$filename = itemhtmlname($post_id, 1, $values, $cc);
				$values = array(
					'filename' => $filename,
					'dateline' => $thetime
				);
				$db->update('filenames', $values, "id='$post_id' AND type='item' AND page=0");
			}
		}
		if($item['category'] != $post_category) refreshitemnum(array($item['category'], $post_category), 'category');
		if($item['section'] != $post_section) refreshitemnum(array($item['section'], $post_section), 'section');
	}
	foreach($file_attach['tmp_name'] as $id => $a) {
		if(!empty($a)) {
			require_once(CORE_ROOT.'include/image.func.php');
			$filename = get_upload_filename($file_attach['name'][$id], $itemid, $post_category);
			if(in_array(fileext($file_attach['name'][$id]), array('php'))) adminmsg($lan['attachexterror'], 'back', 3, 1);
			if($file_attach['error'][$id] == 2) adminmsg($lan['attachtoobig'][0].$setting_maxattachsize.$lan['attachtoobig'][1], 'back', 3, 1);
			if(uploadfile($a, FORE_ROOT.$filename)) {
				if(ispicture($filename)) operateuploadpicture(FORE_ROOT.$filename, $modules[$module]);
				$value = array(
					'itemid' => $itemid,
					'category' => $post_category,
					'section' => $post_section,
					'filename' => $filename,
					'originalname' => $file_attach['name'][$id],
					'filesize' => $file_attach['size'][$id],
					'description' => $post_description[$id],
					'dateline' => $thetime
				);
				$db->insert('attachments', $value);
			}
		}
	}
	if(!empty($ext)) {
		if($db->get_by('id', 'item_exts', "id='$itemid'")) {
			$db->update('item_exts', $extinsertvalue, "id='$itemid'");
		} else {
			$extinsertvalue['id'] = $itemid;
			$db->insert('item_exts', $extinsertvalue);
		}
	}
	if(ifitemtemplateexist($post_category, $post_template)) batchhtml(array($itemid));
	$target = 'index.php?file=admincp&action=items';
	if(!empty($cc['module'])) $target = 'index.php?file=admincp&action=items&module='.$cc['module'];
	//if(akgetcookie('lastcategory') != '') $target = $target.'&category='.akgetcookie('lastcategory'); // added by yao
	if($post_category != '') $target = $target.'&category='.$post_category; // added by yao
	if($post_uploadid != $itemid) {
		$count = $db->get_by('COUNT(*)', 'attachments', "itemid='$post_uploadid'");
		$db->update('attachments', array('itemid' => $itemid), "itemid='$post_uploadid'");
		$db->update('items', array('attach' => $count), "id='$itemid'");
	}
	adminmsg($lan['operatesuccess'], $target);
	//adminmsg($target, $target, 100);
} elseif($get_action == 'items') {
	if(isset($post_batchsubmit)) {
		if(isset($post_batch)) {
			if($post_batchtype == 'delete') {
				batchdeleteitem($post_batch);
				adminmsg($lan['operatesuccess'], 'back');
			} elseif($post_batchtype == 'createhtml') {
				batchhtml($post_batch);
				adminmsg($lan['operatesuccess'], 'back');
			} elseif($post_batchtype == 'setorder') {
				empty($post_neworder) && $post_neworder = 0;
				if(!a_is_int($post_neworder)) $post_neworder = 0;
				$ids = implode(',', $post_batch);
				$value = array(
					'orderby' => $post_neworder,
				);
				$db->update('items', $value, "id IN ($ids)");
				adminmsg($lan['operatesuccess'], 'back');
			} elseif($post_batchtype == 'setcategory') {
				empty($post_newcategory) && $post_newcategory = 1;
				if(!a_is_int($post_newcategory)) $post_newcategory = 1;
				$ids = implode(',', $post_batch);
				$value = array(
					'category' => $post_newcategory,
				);
				$db->update('items', $value, "id IN ($ids)");
				updateitemfilename($post_batch);
				adminmsg($lan['operatesuccess'], 'back');
			}
		} else {
			adminmsg($lan['noitembatch'], 'back', 3, 1);
		}
	}
	$sections = getcache('sections');
	$modules = getcache('modules');
	$selectsections = get_select('section');
	$smarty->assign('selectsections', $selectsections);
	$sql_condition = 'category<>0 ';
	$url_condition = '';
	if(!empty($get_id)) {
		$ids = tidyitemlist($get_id);
		$sql_condition .= " AND id IN ({$ids})";
		$url_condition .= "&id={$get_id}";
	}
	if(!empty($get_key)) {
		$sql_condition .= " AND title LIKE '%{$get_key}%'";
		$url_condition .= "&key=".urlencode($get_key);
	}
	if(!empty($get_editor)) {
		$sql_condition .= " AND editor='{$get_editor}'";
		$url_condition .= "&editor={$get_editor}";
	}
	if(!empty($get_category)) {
		$sql_condition .= " AND category='$get_category'";
		$url_condition .= "&category={$get_category}";
		$categorycache = getcache('category-'.$get_category);
	}
	if(!empty($get_section)) {
		$sql_condition .= " AND section='{$get_section}'";
		$url_condition .= "&section={$get_section}";
	}
	if(empty($get_module)) $get_module = 1;
	if(!empty($categorycache)) $get_module = $categorycache['module'];
	$module = $modules[$get_module];
	if($module == false) adminmsg($lan['parameterwrong'], '', 3, 1);
	if(empty($module['categories'])) adminmsg($lan['nocategorybinded'], 'index.php?file=admincp&action=categories', 3, 1);
	$categories = $module['categories'];
	$selectcategories = '';
	if(empty($nocategoryselect)) {
		$selectcategories = rendercategoryselect();
	}
	$data = $module['data'];
	$fields = array();
	$extflag = 0;
	$dataflag = 0;
	foreach($data['fields'] as $key => $value) {
		if(empty($value['listorder']) || $value['listorder'] <= 0) continue;
		$fields[$key] = $value['listorder'];
		if($extflag == 0 && !in_array($key, $itemfields)) $extflag = 1;
		if($dataflag == 0 && $key == 'data') $dataflag = 1;
	}
	arsort($fields);
	$fieldsheader = '';
	foreach($fields as $key => $field) {
		if(empty($data['fields'][$key]['alias'])) {
			if(substr($key, 0, 7) == 'orderby') {
				$alias = $lan['order'].substr($key, 7);
			} elseif(isset($lan[$key])) {
				$alias = $lan[$key];
			} elseif($key == 'dateline') {
				$alias = $lan['time'];
			} else {
				$alias = $extfields[$key]['name'];
			}
		} else {
			$alias = $data['fields'][$key]['alias'];
		}
		$fieldsheader .= "<td>{$alias}</td>";
	}
	$smarty->assign('moduleid', $get_module);
	$smarty->assign('fieldsheader', $fieldsheader);
	$smarty->assign('selectcategories', $selectcategories);
	$modulecategories = getcategoriesbymodule($get_module);
	if(!empty($modulecategories)) {
		if(empty($get_module) || $get_module == -1) {
			$sql_condition .= " AND category NOT IN (".implode(',', $modulecategories).")";
		} else {
			$sql_condition .= " AND category IN (".implode(',', $modulecategories).")";
		}
	}
	$url_condition .= "&module={$get_module}";
	if(empty($get_orderby))
	{
		$get_orderby = akgetcookie('itemsorderby');
		if(empty($get_category))
			$get_orderby = 'id';
		else
			$get_orderby = 'orderby';
	}

	aksetcookie('itemsorderby', $get_orderby);
	
	!in_array($get_orderby, array('id', 'orderby', 'pageview', 'dateline', 'commentnum', 'lastcomment', 'lastupdate')) && $get_orderby = 'id';
	$url_condition .= "&orderby={$get_orderby}";
	$ipp = empty($module['data']['numperpage']) ? 10 : $module['data']['numperpage'];
	if(isset($get_page)) {
		$page = $get_page;
		aksetcookie('itemspage', $page);
	} else {
		if(isset($cookie_itemspage)) {
			$page = $cookie_itemspage;
		} else {
			$page = 1;
		}
	}
	$page = max($page, 1);
	isset($post_page) && $page = $post_page;
	!a_is_int($page) && $page = 1;
	$start_id = ($page - 1) * $ipp;
	$url = 'index.php?file=admincp&action=items'.ak_htmlspecialchars($url_condition);
	$count = $db->get_by('COUNT(*)', 'items', $sql_condition);
	if($ipp * ($page - 1) > $count) {
		header('location:'.$currenturl.'&page='.ceil($count / $ipp));
		aexit();
	}
	$str_index = multi($count, $ipp, $page, $url);
	$smarty->assign('str_index', $str_index);
	$query = $db->query_by('id', 'items', $sql_condition, " `$get_orderby` DESC", "$start_id,$ipp");
	$str_items = '';
	if(!empty($data['fields'])) {
		foreach($data['fields'] as $_k => $_v) {
			if(empty($_v['default'])) continue;
			if(strpos($_v['default'], ';')) {
				$_t = explode(';', $_v['default']);
				foreach($_t as $_t1) {
					if(strpos($_t1, ',') !== false) {
						$_t2 = explode(',', $_t1);
						$_d[$_k][$_t2[1]] = $_t2[0];
					}
				}
			}
		}
	}
	$items = array();
	while($item = $db->fetch_array($query)) {
		$items[$item['id']] = $item;
	}
	$itemids = array_keys($items);
	if(!empty($itemids)) {
		$itemids = implode(',', $itemids);
		$query = $db->query_by('*', 'items', "id IN ($itemids)");
		while($item = $db->fetch_array($query)) {
			$items[$item['id']] = $item;
		}
		if($extflag) {
			$query = $db->query_by('*', 'item_exts', "id IN ($itemids)");
			while($record = $db->fetch_array($query)) {
				if(!empty($record['value']) && $_value = @unserialize($record['value'])) {
					foreach($_value as $_k => $_v) {
						$items[$record['id']][$_k] = $_v;
					}
				}
			}
		}
		if($dataflag) {
			$query = $db->query_by('itemid,text', 'texts', "itemid IN ($itemids)");
			while($record = $db->fetch_array($query)) {
				$items[$record['itemid']]['data'] = $record['text'];
			}
		}
	}
	$html = $data['html'] + $setting_ifhtml;
	foreach($items as $item) {
		if(!isset($categoriescache[$item['category']])) $categoriescache[$item['category']] = getcategorycache($item['category']);
		$attach = !empty($item['attach']) ? "<img src='images/admin/attach.gif' title='{$lan['haveattach']}:{$item['attach']}' alt='{$lan['haveattach']}:{$item['attach']}'>&nbsp;" : '';
		$picture = pictureurl($item['picture'], $attachurl);
		$picture = $picture ? '<a href="'.$picture.'" target="_blank"><img src="images/admin/picture.gif" title="'.$lan['havepicture'].'" border="0"></a>&nbsp;' : '';
		$checkbox = "<input type=\"checkbox\" name=\"batch[]\" value=\"{$item['id']}\">";
		$category = isset($categoriescache[$item['category']]) ? $categoriescache[$item['category']]['category'] : '-';
		$section = isset($sections[$item['section']]) ? $sections[$item['section']]['section'] : '-';
		$title = htmltitle(ak_htmlspecialchars($item['title']), $item['titlecolor'], $item['titlestyle']);
		$draft = '';
		if($item['draft']) $draft = '['.green("{$lan['draft']}").']'."[<a id={$item['id']} class='release' href='#'>".green($lan['release']).'</a>]';
		$str_moduleitems = '';
		foreach($fields as $key => $field) {
			if($field <= 0) continue;
			if($key == 'title') {
				$page = '';
				if($item['pagenum'] > 0) $page = " ({$item['pagenum']})";
				$str_moduleitems .= "<td><div class='yztitle'>{$attach}{$picture}<a href=\"index.php?file=admincp&action=edititem&id={$item['id']}\" title='{$title}'>{$title}</a>$page $draft</div></td>";
			} elseif($key == 'category') {
				$str_moduleitems .= "<td><div class='yzcategory' title='{$category}'>{$category}</div></td>";
			} elseif($key == 'section') {
				$str_moduleitems .= "<td>{$section}</td>";
			} elseif($key == 'dateline') {
				$str_moduleitems .= "<td class='mininum' title='".date('Y-m-d H:i:s', $item['dateline'])."'>".date('Y-m-d', $item['dateline'])."</td>";
			} elseif($key == 'filename') {
				$str_moduleitems .= "<td><div class='yzfilename' title='{$item[$key]}'>{$item[$key]}</div></td>";
			} else {
				if(!empty($_d[$key])) {
					if(isset($item[$key]) && is_array($_d[$key])) {
						if(isset($_d[$key][$item[$key]])) {
							$str_moduleitems .= "<td {$_d[$key][$item[$key]]} 1>{$_d[$key][$item[$key]]}</td>";
						} else {
							$str_moduleitems .= "<td><div class='yz{$key}2' title='{$item[$key]}' >{$item[$key]}</div></td>";
						}
					} else {
						$_key = $_d[$key];
						if(strpos($_key, '[id]') !== false) $_key = str_replace('[id]', $item['id'], $_key);
						if(isset($item[$key]) && strpos($_key, '[value]') !== false) {
							$_key = str_replace('[value]', $item[$key], $_key);
						}
						$str_moduleitems .= "<td><div class='yz{$_d[$key]}3' title='{$_key}' >{$_key}</div></td>";
					}
				} elseif(isset($item[$key])) {
					$str_moduleitems .= "<td><div class='yz{$key}4' title='{$item[$key]}'>{$item[$key]}</div></td>";
				} elseif($key == 'comment') {
					$str_moduleitems .= "<td><div class='yzcommentnum' title='{$item['commentnum']}'}>{$item['commentnum']}</div></td>";
				} else {
					$str_moduleitems .= "<td>-</td>";
				}
			}
		}
		$str_items .= "<tr class='yztr'><td>{$checkbox}&nbsp;{$item['id']}</td>{$str_moduleitems}</td><td align='center'><a href='index.php?file=admincp&action=deleteitem&id={$item['id']}&vc={$vc}' onclick='return confirmdelete()'>".alert($lan['delete'])."</a></td>";
		if($data['page'] > 0) {
			$str_items .= "<td align='center'><a href='{$homepage}akcms_item.php?id={$item['id']}' target='_blank'>{$lan['preview']}</a></td>";
			$realurl = itemurl($item['id'], 1, $item, $categoriescache[$item['category']]);
			$str_items .= "<td align='center'><a href='$realurl' target='_blank'>{$lan['realurl']}</a></td>";
		}
		if($data['page'] > 0 && $html > 0) {
			$str_items .= "<td align='center'><a href='index.php?file=admincp&action=createhtml&id={$item['id']}' target='work'>{$lan['createhtml']}</a></td>\n";
		}
		$str_items .= "</tr>";
	}
	if($str_items == '') $str_items = '<tr><td colspan="15">'.$lan['item_no'].'</td></tr>';
	$smarty->assign('str_items', $str_items);
	$smarty->assign('html', $html);
	$smarty->assign('page', $data['page']);
	$smarty->assign('indexurl', $url);
	$smarty->assign('get', ak_htmlspecialchars($_GET));
	displaytemplate('admincp_items.htm');
} elseif($get_action == 'publish') {
	error_log(serialize($_GET), 3, AK_ROOT.'test.log');
	if($get_pub != 1) echo 'false';
	$db->update('items', array('draft' => '0'), "id='$get_id'");
	echo 'true';
} elseif($get_action == 'specialpages') {
	if(!isset($get_job) && !isset($get_id)) {
		//$query = $db->query_by('*', 'items', 'category=0', 'id');
		$query = $db->query_by('*', 'items', 'category=0', 'title');
		$str_pages = '';
		while($page = $db->fetch_array($query)) {
			$createhtml_text = '<a href="index.php?file=admincp&action=createhtml&id='.$page['id'].'&category=0" target="work">'.$lan['createhtml'].'</a>';
			$delete_text = "<a href=\"index.php?file=admincp&action=deleteitem&id={$page['id']}&vc={$vc}\" onclick=\"return confirmdelete()\">".alert($lan['delete'])."</a>";
			$str_pages .= "<tr class='yztr'><td>{$page['id']}</td><td><a href=\"index.php?file=admincp&action=specialpages&id={$page['id']}\">{$page['title']}</a></td><td><a href=\"index.php?file=admincp&action=template&template=,{$page['template']}\">{$page['template']}</a></td><td>{$page['filename']}</td><td>{$page['pageview']}</td>";
			$str_pages .= "<td align='center'><a href='{$homepage}akcms_item.php?id={$page['id']}' target='_blank'>{$lan['preview']}</a></td>";
			if($page['filename'] != '') {
				$realurl = itemurl($page['id'], 1, $page);
				$str_pages .= "<td align='center'><a href='$realurl' target='_blank'>{$lan['realurl']}</a></td><td align='center'>{$createhtml_text}</td>";
			} else {
				$str_pages .= "<td></td><td></td>";
			}
			$str_pages .= "<td align='center'>{$delete_text}</td></tr>\n";
		}
		if($str_pages == '') $str_pages = '<tr><td colspan="10">'.$lan['specialpage_no'].'</td></tr>';
		$selecttemplates = get_select_templates();
		$smarty->assign('str_pages', $str_pages);
		$smarty->assign('str_templates', $selecttemplates);
		displaytemplate('admincp_specialpages.htm');
	} elseif(isset($get_job) && $get_job == 'newpage') {
		if(empty($post_pagename) || empty($post_template)) adminmsg($lan['allarerequired'], 'back', 3, 1);
		$value = array(
			'title' => $post_pagename,
			'template' => $post_template,
			'dateline' => $thetime,
			'lastupdate' => $thetime,
			'editor' => $admin_id
		);
		$db->insert('items', $value);
		$itemid = $db->insert_id();
		if(!empty($post_data)) $db->insert('texts', array('itemid' => $itemid, 'text' => $post_data));
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=specialpages');
	} elseif(!empty($get_id)) {
		if(!a_is_int($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
		if(!isset($post_saveeditpage)) {
			$page = $db->get_by('*', 'items', "id='$get_id'");
			if(empty($page)) adminmsg($lan['parameterwrong'], '', 3, 1);
			$text = $db->get_by('text', 'texts', "itemid='$get_id'");
			$selecttemplates = get_select_templates();
			$smarty->assign('id', $get_id);
			$smarty->assign('pagename', $page['title']);
			$smarty->assign('filename', $page['filename']);
			$smarty->assign('data', ak_htmlspecialchars($text));
			$smarty->assign('template', $page['template']);
			$smarty->assign('str_templates', $selecttemplates);
			displaytemplate('admincp_specialpage.htm');
		} else {
			if(empty($post_pagename) || empty($post_template)) adminmsg($lan['allarerequired'], 'back', 3, 1);
			$page = $db->get_by('*', 'items', "id='$get_id'");
			if(empty($page)) adminmsg($lan['parameterwrong'], '', 3, 1);
			if($post_filename != '') {
				if(!preg_match('/^\//', $post_filename)) adminmsg($lan['pagepathroot'], 'back', 3, 1);
				if(!empty($post_filename) && strpos($post_filename, '.') === false) $post_filename .= $setting_htmlexpand;
				$filenamechecked = checkfilename($post_filename, 'noempty');
				if($filenamechecked != '') adminmsg($filenamechecked, 'back', 3, 1);
				$htmlfilename = substr($post_filename, 1);
				if($page = $db->get_by('id', 'filenames', "filename='$htmlfilename'")) {
					if($page != $get_id) adminmsg($lan['fileexist'], 'back', 6, 1);
				}
			}
			$value = array(
				'title' => $post_pagename,
				'filename' => $post_filename,
				'template' => $post_template,
			);
			$db->update('items', $value, "id='$get_id'");
			if($db->get_by('*', 'texts', "itemid='$get_id'")) {
				if(empty($post_data)) {
					$db->delete('texts', "itemid='$get_id'");
				} else {
					$db->update('texts', array('text' => $post_data), "itemid='$get_id'");
				}
			} else {
				if(!empty($post_data)) {
					$db->insert('texts', array('text' => $post_data, 'itemid' => $get_id));
				}
			}
			if($post_filename != '') {
				if($db->get_by('*', 'filenames', "id='$get_id'")) {
					$value = array(
						'filename' => $htmlfilename
					);
					$db->update('filenames', $value, "id='$get_id' AND type='item'");
				} else {
					$value = array(
						'filename' => $htmlfilename,
						'id' => $get_id,
						'dateline' => $thetime,
						'type' => 'item',
						'page' => 0
					);
					$db->insert('filenames', $value);
				}
				batchhtml(array($get_id));
			}
			adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=specialpages');
		}
	}
} elseif($get_action == 'deleteitem') {
	vc();
	if(!isset($get_id) || !a_is_int($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
	batchdeleteitem(array($get_id));
	if(!isset($get_returnlist)) {
		adminmsg($lan['operatesuccess'], 'back');
	} else {
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=items');
	}
} elseif($get_action == 'comments') {
	(empty($get_id) || !a_is_int($get_id)) && adminmsg($lan['parameterwrong'], '', 3, 1);
	$item = $db->get_by('*', 'items', "id='$get_id'");
	$query = $db->query_by('*', 'comments', "itemid='$get_id'", 'dateline');
	$str_comments = '';
	$i = 0;
	while($comment = $db->fetch_array($query)) {
		$i ++;
		$str_comments .= "<tr bgcolor='#FFFFFF'><td><div class='righttop'><a href='index.php?file=admincp&action=deletecomment&id={$comment['id']}&itemid={$comment['itemid']}&vc={$vc}' onclick='return confirmdelete()'>{$lan['delete']}</a>&nbsp;<a href='index.php?file=admincp&action=commentdenyip&ip={$comment['ip']}&itemid={$comment['itemid']}&vc={$vc}' onclick='return confirmdenyip()'>{$lan['denyip']}</a>&nbsp<a href='javascript:review({$comment['id']})'>{$lan['review']}</a></div>{$lan['title']}:".ak_htmlspecialchars($comment['title'])."&nbsp;|
		{$lan['name']}:".ak_htmlspecialchars($comment['username'])."&nbsp;|
		{$lan['time']}:".date('Y-m-d H:i:s', $comment['dateline'])."&nbsp;|
		IP:<a href='index.php?file=admincp&action=allcomments&ip={$comment['ip']}' target='_parent'>{$comment['ip']}</a></td></tr>
		<tr bgcolor='#FFFFFF'><td style='border-bottom:1px solid #D6E0EF;'>".nl2br(ak_htmlspecialchars($comment['message']));
		if($comment['review'] != '') $str_comments .= "<br />{$lan['review']}:<span class='red'>".ak_htmlspecialchars($comment['review']).'</span>';
		$str_comments .= "<div id='review{$comment['id']}' class='reviewdiv'><form action='index.php?file=admincp&action=reviewcomment' method='post'><input type='hidden' name='id' value='{$comment['id']}'><input type='hidden' name='itemid' value='{$get_id}'><textarea id='textarea{$comment['id']}' name='review' cols='80' rows='2'>".ak_htmlspecialchars($comment['review'])."</textarea><br><input type='submit' value='{$lan['save']}' id='save{$comment['id']}'></form></div></td></tr>";
	}
	if($i != $item['commentnum']) {
		$value = array(
			'commentnum' => $i
		);
		$db->update('items', $value, "id='$get_id'");
	}
	$str_comments == '' && $str_comments = "<tr bgcolor='#FFFFFF'><td>{$lan['commentempty']}</td></tr>";
	$smarty->assign('comments', $str_comments);
	$smarty->assign('num', $i);
	displaytemplate('admincp_comments.htm');
	aexit();
} elseif($get_action == 'allcomments') {
	$where = '1';
	if(!empty($get_ip)) $where = "ip='$get_ip'";
	if(empty($get_page)) $get_page = 1;
	if(!empty($post_page)) $get_page = $post_page;
	$numperpage = 8;
	$count = $db->get_by('COUNT(id) as c', 'comments', $where);
	$str_index = multi($count, $numperpage, $get_page, 'index.php?file=admincp&action=allcomments');
	$query = $db->query_by('*', 'comments', $where, 'dateline DESC', (($get_page - 1) * $numperpage).",$numperpage");
	$str_comments = '';
	$i = 0;
	$items = array();
	while($comment = $db->fetch_array($query)) {
		$i ++;
		if(!in_array($comment['itemid'], $items)) $items[] = $comment['itemid'];
		$str_comments .= "<tr bgcolor='#FFFFFF'><td><div class='righttop'><a href='index.php?file=admincp&action=deletecomment&all=1&id={$comment['id']}&itemid={$comment['itemid']}&vc={$vc}' onclick='return confirmdelete()'>{$lan['delete']}</a>&nbsp;<a href='index.php?file=admincp&action=commentdenyip&all=1&ip={$comment['ip']}&itemid={$comment['itemid']}&vc={$vc}' onclick='return confirmdenyip()'>{$lan['denyip']}</a>&nbsp<a href='javascript:review({$comment['id']})'>{$lan['review']}</a></div>ID:{$comment['id']} | {$lan['title']}:".ak_htmlspecialchars($comment['title'])."&nbsp;|
		{$lan['name']}:".ak_htmlspecialchars($comment['username'])."&nbsp;|
		".date('m-d H:i:s', $comment['dateline'])."&nbsp;|
		IP:<a href='index.php?file=admincp&action=allcomments&ip={$comment['ip']}'>{$comment['ip']}</a> @[{$comment['itemid']}]</td></tr>
		<tr bgcolor='#FFFFFF'><td style='border-bottom:1px solid #D6E0EF;'>".nl2br(ak_htmlspecialchars($comment['message'])).'&nbsp;';
		if($comment['review'] != '') $str_comments .= "<br />{$lan['review']}:<span class='red'>".ak_htmlspecialchars($comment['review']).'</span>';
		$str_comments .= "<div id='review{$comment['id']}' class='reviewdiv'><form action='index.php?file=admincp&action=reviewcomment' method='post'><input type='hidden' name='all' value='1'><input type='hidden' name='id' value='{$comment['id']}'><input type='hidden' name='itemid' value='{$comment['id']}'><textarea id='textarea{$comment['id']}' name='review' cols='80' rows='2'>".ak_htmlspecialchars($comment['review'])."</textarea><br><input type='submit' value='{$lan['save']}' id='save{$comment['id']}'></form></div></td></tr>";
	}
	if(!empty($items)) {
		$ids = implode(',', $items);
		$query = $db->query_by('*', 'items', "id IN ($ids)");
		while($item = $db->fetch_array($query)) {
			$_items[$item['id']] = $item;
		}
		foreach($items as $id) {
			$to = '';
			if(isset($_items[$id])) {
				$item = $_items[$id];
				$url = itemurl($id, 1, $item);
				$to = "@<a href='$url' target='_blank'>".$_items[$id]['title']."</a> <a href='index.php?file=admincp&action=edititem&id={$id}'>>></a>";
			}
			$str_comments = str_replace("@[{$id}]", $to, $str_comments);
		}
	}
	$str_comments == '' && $str_comments = "<tr bgcolor='#FFFFFF'><td>{$lan['commentempty']}</td></tr>";
	$smarty->assign('comments', $str_comments);
	$smarty->assign('str_index', $str_index);
	$smarty->assign('num', $i);
	displaytemplate('admincp_allcomments.htm');
} elseif($get_action == 'deletecomment') {
	vc();
	(empty($get_id) || !a_is_int($get_id)) && adminmsg($lan['parameterwrong'], '', 3, 1);
	$db->delete('comments', "id='{$get_id}'");
	refreshcommentnum($get_itemid);
	if(empty($get_all)) {
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=comments&id='.$get_itemid);
	} else {
		adminmsg($lan['operatesuccess'], 'back');
	}
} elseif($get_action == 'commentdenyip') {
	$comment_deny_ip_dic = AK_ROOT.'configs/comment_deny_ips.txt';
	empty($get_ip) && adminmsg($lan['parameterwrong'], '', 3, 1);
	$commentdenyips_data = readfromfile($comment_deny_ip_dic);
	$commentdenyips = explode("\n", $commentdenyips_data);
	if(!in_array($get_ip, $commentdenyips)) {
		if($commentdenyips_data == '') {
			$commentdenyips_data = $get_ip;
		} else {
			$commentdenyips_data = "\n".$get_ip;
		}
		writetofile($commentdenyips_data, $comment_deny_ip_dic);
	}
	deletecommentbyip($get_ip);
	refreshcommentnum($get_itemid);
	adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=comments&id='.$get_itemid);
} elseif($get_action == 'createhtml') {
	if(empty($get_id)) debug($lan['parameterwrong'], 1, 1);
	if(empty($get_category)) $get_category = $db->get_by('category', 'items', "id='$get_id'");
	$category = getcategorycache($get_category);
	if($get_category != 0 && ($category['html'] == -1 || ($setting_ifhtml == 0 && $category['html'] == 0))) debug($lan['functiondisabled'], 1, 1);
	$result = batchhtml(array($get_id));
	if($result === false) debug($lan['createhtmlerror'], 1, 1);
	debug($lan['operatesuccess'], 1, 1);
} elseif($get_action == 'templates') {
	checkcreator();
	if(!isset($post_templatename)) {
		$str_maintemplates = '';
		$str_subtemplates = '';
		$dh = opendir($templatedir);
		$files = array();
		while(false !== ($filename = readdir($dh))) {
			if($filename != '.' && $filename != '..') $files[] = $filename;
		}
		list($i, $j) = array(0, 0);
		sort($files);
		foreach($files as $id => $file) {
			if(substr($file, -4) != '.htm') continue;
			if($file == 'index.htm') continue;
			if(substr($file, 0, 1) == '.') continue;
			if($file == 'index.htm') continue;
			if(substr($file, 0, 1) == ',') {
				$i ++;
				$file = substr($file, 1);
				$str_maintemplates .= "<tr class='yztr'><td>{$i}</td>
					<td><a href=\"index.php?file=admincp&action=template&template=,{$file}\">{$file}&nbsp;{$lan['edit']}</a>&nbsp;<a href=\"index.php?file=admincp&action=deletetemplate&vc={$vc}&template=,{$file}\" onclick=\"return confirmdelete()\">".alert($lan['delete'])."</a></td>";
			} else {
				$j ++;
				$str_subtemplates .= "<tr class='yztr'><td>{$j}</td>
					<td><a href=\"index.php?file=admincp&action=template&template={$file}\">{$file}&nbsp;{$lan['edit']}</a>&nbsp;<a href=\"index.php?file=admincp&action=deletetemplate&vc={$vc}&template={$file}\" onclick=\"return confirmdelete()\">".alert($lan['delete'])."</a></td>
				</tr>";
			}
		}
		$smarty->assign('str_maintemplates', $str_maintemplates);
		$smarty->assign('str_subtemplates', $str_subtemplates);
		displaytemplate('admincp_templates.htm');
	} else {
		if(empty($post_templatename) || !preg_match('/^[0-9a-zA-Z_]+$/i', $post_templatename)) adminmsg($lan['templatenameerror'], 'back', 3, 1);
		$prefix = $post_prefix;
		$filename = $templatedir.$prefix.$post_templatename.'.htm';
		if(file_exists($filename)) adminmsg($lan['templateexit'] , 'back', 3, 1);
		$text = $lan['newtemplate'];
		if(!writetofile($text, $filename)) adminmsg($lan['cantcreatetemplate'] , 'back', 3, 1);
		updatecache('templates');
		go('index.php?file=admincp&action=templates');
	}
}elseif($get_action == 'template') {
	checkcreator();
	if(!isset($get_job)) {
		if(!is_writable($templatedir.$get_template)) adminmsg($lan['templatenotwritable'], '', 3, 1);
		$str_template = ak_htmlspecialchars(readfromfile($templatedir.$get_template));
		$smarty->assign('str_template', $str_template);
		$smarty->assign('template', $get_template);
		$smarty->assign('language', $language);
		displaytemplate('admincp_template.htm');
	} elseif($get_job == 'delete') {
		$filename = $templatedir.$get_template;
		if(preg_match('/^,/i', $get_template)) {
			$template = substr($get_template, 1);
			if($db->get_by('*', 'items', "template='$template'")) adminmsg($lan['deltemplatehasused'], 'index.php?file=admincp&action=templates', 3, 1);
		}
		if(!file_exists($filename)) adminmsg($lan['notemplate'] , 'index.php?file=admincp&action=templates');
		if(unlink($filename) === false) {
			adminmsg($lan['cantdeltemplate'] , 'index.php?file=admincp&action=templates');
		} else {
			adminmsg($lan['operatesuccess'] , 'index.php?file=admincp&action=templates');
		}
	} elseif($get_job == 'save') {
		if(!is_writable($templatedir.$post_template)) adminmsg($lan['templatenotwritable'], '', 3, 1);
		if(!writetofile($post_html, $templatedir.$post_template)) adminmsg($lan['templatenotwritable'], '', 3, 1);
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=templates');
	}
	updatecache('templates');
} elseif($get_action == 'variables') {
	checkcreator();
	if(!isset($get_job)) {
		$query = $db->query_by('*', 'variables', '1', 'variable');
		$str_variables = '';
		$i = 0;
		$typeoptions = "<option value='string'>{$lan['string']}</option><option value='int'>{$lan['number']}</option><option value='pass'>{$lan['password']}</option><option value='radio'>{$lan['radio']}</option><option value='checkbox'>{$lan['checkbox']}</option><option value='text'>{$lan['text']}</option><option value='richtext'>{$lan['richtext']}</option><option value='category'>{$lan['category']}</option><option value='categories'>{$lan['category']}({$lan['multiple']})</option><option value='section'>{$lan['section']}</option><option value='picture'>{$lan['picture']}</option>";
		while($v = $db->fetch_array($query)) {
			$i ++;
			$str_variables .= "<tr class='yztr'><td width='30' valign='top'>{$i}</td>
			<td width='160' valign='top'>{$v['variable']}<input type='hidden' value='{$v['variable']}' name='variable_{$v['variable']}'></td>
			<td valign='top'><input type='text' style='width:158px;' name='description_{$v['variable']}' value='".ak_htmlspecialchars($v['description'])."' /></td>
			<td valign='top'><input type='text' style='width:88px;' name='standby_{$v['variable']}' value='".ak_htmlspecialchars($v['standby'])."' /></td>
			<td valign='top'><select id='type_{$v['variable']}' name='type_{$v['variable']}'>$typeoptions</select><script>$('#type_{$v['variable']}').val('{$v['type']}');</script></td>";
			$str_variables .= "<td>".renderinput($v['variable'], $v['type'], $v['standby'], $v['value'])."</td>";
			$str_variables .= "<td><input type='button' value='{$lan['delete']}' onclick=\"deletevariable('{$v['variable']}')\"></td>";
			$str_variables .= "</tr>";
		}
		$smarty->assign('typeoptions', $typeoptions);
		$smarty->assign('variables', $str_variables);
		displaytemplate('admincp_variables.htm');
	} elseif($get_job == 'delete') {
		vc();
		$db->delete('variables', "variable='$get_variable'");
		updatecache('globalvariables');
		go('index.php?file=admincp&action=variables');
	} elseif($get_job == 'save') {
		$query = $db->query_by('*', 'variables', "1");
		$variables = array();
		while($variable = $db->fetch_array($query)) {
			$variables[$variable['variable']] = $variable;
		}
		foreach($_POST as $k => $v) {
			if(strpos($k, 'variable_') === 0) {
				$key = substr($k, 9);
				if(a_is_int($key)) {
					$variable = $v;
				} else {
					$variable = $key;
				}
				if(!isset($_POST[$key])) $_POST[$key] = '';
				if(is_array($_POST[$key])) $_POST[$key] = implode(',', $_POST[$key]);
				if(!empty($_FILES[$key.'_upload']['name'])) {
					$_u = $_FILES[$key.'_upload'];
					$headpicext = fileext($_u['name']);
					if(!ispicture($_u['name'])) adminmsg($lan['pictureexterror'], 'back', 3, 1);
					$filename = get_upload_filename($_u['name'], 0, 0, 'preview');
					if(uploadfile($_u['tmp_name'], FORE_ROOT.$filename)) $_POST[$key] = $filename;
				}
				$value = array(
					'variable' => $variable,
					'type' => $_POST['type_'.$key]
				);
				if(isset($variables[$key])) {
					$variable = $variables[$key];
					$value['description'] = $_POST['description_'.$key];
					$value['standby'] = $_POST['standby_'.$key];
					$value['value'] = $_POST[$key];
					if($variable['description'] == $value['description'] && $variable['standby'] == $value['standby'] && $variable['type'] == $value['type'] && $variable['value'] == $value['value']) continue;
					$db->update('variables', $value, "variable='$v'");
				} else {
					$db->insert('variables', $value);
				}
			}
		}
		updatecache('globalvariables');
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=variables');
	}
} elseif($get_action == 'variable') {
	$variables = explode(',', $get_variable);
	$input = '';
	foreach($variables as $v) {
		$variable = $db->get_by('*', 'variables', "variable='$v'");
		if(empty($variable)) continue;
		if(strpos($variable['description'], '|') === false) {
			$title = $variable['description'];
			$description = '';
		} else {
			list($title, $description) = explode('|', $variable['description']);
		}
		$input .= "<tr><td valign=\"top\"><b>{$title}</b><br>{$description}</td><td valign=\"top\" width=\"300\">".renderinput($v, $variable['type'], $variable['standby'], $variable['value'])."</td></tr>\n";
	}
	$smarty->assign('html', $input);
	displaytemplate('admincp_variable.htm');
} elseif($get_action == 'savevariable') {
	foreach($_POST as $k => $v) {
		if(is_array($v)) $v = implode(',', $v);
		$db->update('variables', array('value' => $v), "variable='$k'");
	}
	updatecache('globalvariables');
	adminmsg($lan['operatesuccess'], 'back');
} elseif($get_action == 'filters') {
	checkcreator();
	if(!isset($get_job)) {
		$query = $db->query_by('*', 'filters');
		$filters = '';
		$i = 0;
		while($f = $db->fetch_array($query)) {
			$i ++;
			$filters .= "<tr><td width=\"30\" valign='top'>{$f['id']}</td><td>".nl2br(ak_htmlspecialchars($f['data']))."</td><td valign='top'><a href='index.php?file=admincp&action=filters&job=edit&id={$f['id']}'>{$lan['edit']}</a></td>";
			$filters .= "<td valign='top'><a href=\"javascript:deletefilter('{$f['id']}')\">{$lan['delete']}</a></td></tr>";
		}
		$smarty->assign('filters', $filters);
		displaytemplate('admincp_filters.htm');
	} elseif($get_job == 'new') {
		$value = array('data' => $post_filter);
		$db->insert('filters', $value);
		updatecache('filters');
		go('index.php?file=admincp&action=filters');
	} elseif($get_job == 'edit') {
		if(empty($post_id)) {
			if(empty($get_id)) aexit();
			$filter = $db->get_by('*', 'filters', "id='$get_id'");
			if(empty($filter)) aexit();
			$smarty->assign('data', ak_htmlspecialchars($filter['data']));
			$smarty->assign('id', $get_id);
			displaytemplate('admincp_filter.htm');
		} else {
			$value = array('data' => $post_filter);
			$db->update('filters', $value, "id='$post_id'");
			updatecache('filters');
			go('index.php?file=admincp&action=filters');
		}
	} elseif($get_job == 'delete') {
		vc();
		$db->delete('filters', "id='$get_id'");
		updatecache('filters');
		go('index.php?file=admincp&action=filters');
	}
} elseif($get_action == 'createcategory') {
	if(empty($setting_ifhtml)) adminmsg($lan['createhtml'].$lan['functiondisabled'].'<br><br><a href="index.php?file=setting&action=functions">'.$lan['open'].'</a>', '', 0, 1);
	if(!empty($get_id)) {
		batchcategoryhtml($get_id);
		adminmsg($lan['inisuccess'], 'index.php?file=admincp&action=createcategory&job=process');
	} elseif(isset($post_cid)) {
		foreach($post_cid as $cid) {
			batchcategoryhtml($cid);
		}
		adminmsg($lan['inisuccess'], 'index.php?file=admincp&action=createcategory&job=process');
	} elseif(isset($get_job) && $get_job == 'process') {
		if(operatecreatecategoryprocess() === true) {
			adminmsg($lan['operatesuccess']);
		} else {
			adminmsg($lan['execution'], 'index.php?file=admincp&action=createcategory&job=process');
		}
	} else {
		$query = $db->query_by('id,category,html', 'categories', "html>=0");
		$categorieslist = '';
		while($c = $db->fetch_array($query)) {
			if($c['html'] == 1 || ($setting_ifhtml && $c['html'] == 0)) {
				$categorieslist .= "<tr><td><input type='checkbox' checked=true name='cid[]' value='{$c['id']}'></td>";
				$categorieslist .= "<td><a href='index.php?file=admincp&action=editcategory&id={$c['id']}'>{$c['category']}</a></td>";
				$categorieslist .= "<td><a href='index.php?file=admincp&action=createcategory&id={$c['id']}'>{$lan['createhtml']}</a></td></tr>";
			}
		}
		$smarty->assign('categorieslist', $categorieslist);
		displaytemplate('admincp_createcategory.htm');
	}
} elseif($get_action == 'createitem') {
	$taskkey = 'batchcreateitem';
	if(empty($setting_ifhtml)) adminmsg($lan['createhtml'].$lan['functiondisabled'].'<br><br><a href="index.php?file=setting&action=functions">'.$lan['open'].'</a>', '', 0, 1);
	require_once 'include/task.file.func.php';
	if(isset($get_category)) {
		if($get_category > 0) {
			$where = "category='$get_category'";
		} elseif($get_category == 0) {
			$where = "category>0";
		} else {
			$where = '1';
		}
		$query = $db->query_by('id', 'items', $where);
		$items = array();
		while($item = $db->fetch_array($query)) {
			$items[] = $item['id'];
		}
		if(empty($items)) adminmsg($lan['noitem'], 'index.php?file=admincp&action=createitem');
		addtasks($taskkey, $items);
		showprocess($lan['running'], 'index.php?file=admincp&action=createitem&process=1&step='.$get_step.'&all='.count($items));
		$batchitemready = $lan['batchitemready'];
		$batchitemready = str_replace('(*1)', count($items), $batchitemready);
		adminmsg($batchitemready, 'index.php?file=admincp&action=createitem&process=1&step='.$get_step.'&all='.count($items), 3);
	} elseif(!empty($get_process)) {
		$tasks = gettask($taskkey, $get_step);
		if(empty($tasks)) aexit(100);
		batchhtml($tasks);
		$finishedpercent = gettaskpercent($taskkey);
		aexit("$finishedpercent\t\t");
	} else {
		$categories = get_select('category');
		$smarty->assign('categories', $categories);
		displaytemplate('admincp_createitem.htm');
	}
} elseif($get_action == 'createsection') {
	if(empty($setting_ifhtml)) adminmsg($lan['createhtml'].$lan['functiondisabled'].'<br><br><a href="index.php?file=setting&action=functions">'.$lan['open'].'</a>', '', 0, 1);
	if(isset($get_id)) {
		if(empty($get_id)) {
			$sections = getcache('sections');
			$query = $db->query_by('*', 'sections', '', 'id');
			$batchsections = array();
			foreach($categories as $c) {
				$batchsections[] = $c['id'];
			}
			batchsectionhtml($batchcategories);
			adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=createsection');
		} else {
			batchsectionhtml($get_id);
			adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=createsection&job=process');
		}
	} elseif(isset($post_cid)) {
		foreach($post_cid as $cid) {
			batchsectionhtml($cid);
		}
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=sections');
	} elseif(isset($get_job) && $get_job == 'process') {
		if(operatecreatesectionprocess() === true) {
			adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=sections');
		} else {
			adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=createsection&job=process');
		}
	}
} elseif($get_action == 'delattach') {
	if($attach = $db->get_by('*', 'attachments', "id='{$get_id}'")) {
		@unlink(FORE_ROOT.$attach['filename']);
		$db->delete('attachments', "id={$get_id}");
		if(!$db->get_by('*', 'attachments', "itemid='{$attach['itemid']}'")) {
			$db->update('items', array('attach' => 0), "id='{$attach['itemid']}'");
		}
		debug($lan['attachdeleted'], 1, 1);
	} else {
		debug($lan['attachnotfound'], 1, 1);
	}
} elseif($get_action == 'manual') {
	debug('manual');
} elseif($get_action == 'modules') {
	checkcreator();
	if(empty($get_job)) {
		$query = $db->query_by('*', 'modules', '', 'id');
		$moduleslist = '';
		while($module = $db->fetch_array($query)) {
			$moduleslist .= "<tr><td>{$module['id']}</td><td><a href=\"index.php?file=admincp&action=modules&job=editmodule&id={$module['id']}\">{$module['modulename']}</a></td><td>";
			if($module['id'] > 1) $moduleslist .= "<a href=\"index.php?file=admincp&action=modules&job=del&vc={$vc}&id={$module['id']}\">{$lan['del']}</a>";
			$moduleslist .= "</td></tr>";
		}
		$smarty->assign('moduleslist', $moduleslist);
		displaytemplate('admincp_modules.htm');
	} elseif($get_job == 'addmodule') {
		$fieldshtml = modulefields();
		$smarty->assign('page', 1);
		$smarty->assign('numperpage', 10);
		$smarty->assign('fieldshtml', $fieldshtml);
		displaytemplate('admincp_module.htm');
	} elseif($get_job == 'del') {
		vc();
		if(empty($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
		$_sql = "DELETE FROM {$tablepre}_modules WHERE id='$get_id'";
		$db->query($_sql);
		updatecache('modules');
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=modules');
	} elseif($get_job == 'editmodule') {
		if(!isset($get_id)) adminmsg($lan['parameterwrong'], '', 3, 1);
		$module = $db->get_by('*', 'modules', "id='{$get_id}'");
		$data = ak_unserialize($module['data']);
		$templates = get_select_templates();
		$fieldshtml = modulefields($data['fields']);
		$smarty->assign('modulename', $module['modulename']);
		$smarty->assign('fieldshtml', $fieldshtml);
		$smarty->assign('id', $get_id);
		$smarty->assign('templates', $templates);
		foreach($data as $k => $v) {
			if($k == 'id') continue;
			$smarty->assign($k, $v);
		}
		displaytemplate('admincp_module.htm');
	} elseif($get_job == 'savemodule') {
		if(empty($post_modulename)) debug('error', 1);
		$data = array();
		foreach($itemfields as $field) {
			$_alias = "post_{$field}_alias";
			$_order = "post_{$field}_order";
			$_listorder = "post_{$field}_listorder";
			$_description = "post_{$field}_description";
			$_default = "post_{$field}_default";
			$_size = "post_{$field}_size";
			$_type = "post_{$field}_type";
			$data['fields'][$field] = array(
				'alias' => $$_alias,
				'order' => $$_order,
				'listorder' => $$_listorder,
				'description' => $$_description,
				'default' => $$_default,
				'size' => isset($$_size) ? $$_size : '',
				'type' => isset($$_type) ? $$_type : '',
			);
			if($field == 'title') {
				$data['fields']['title']['iftitlestyle'] = !empty($post_iftitlestyle);
				$data['fields']['title']['ifinitial'] = !empty($post_ifinitial);
			}
		}
		foreach($_POST as $_k => $_v) {
			if(substr($_k, 0, 8) == 'extfield' && strlen($_k) < 11) {
				$_id = substr($_k, 8);
				$_key = "post_extfield{$_id}";
				$_alias = "post_extfield_alias{$_id}";
				if(empty($$_alias) || empty($$_key)) continue;
				$_order = "post_extfield_order{$_id}";
				$_listorder = "post_extfield_listorder{$_id}";
				$_description = "post_extfield_description{$_id}";
				$_default = "post_extfield_default{$_id}";
				$_size = "post_extfield_size{$_id}";
				$_type = "post_extfield_type{$_id}";
				$data['fields']['_'.$$_key] = array(
					'alias' => $$_alias,
					'order' => $$_order,
					'listorder' => $$_listorder,
					'description' => $$_description,
					'default' => $$_default,
					'size' => isset($$_size) ? $$_size : '',
					'type' => isset($$_type) ? $$_type : '',
				);
			}
		}
		foreach($_POST as $k => $v) {
			if(strpos($k, '_') === false) $data[$k] = $v;
		}
		$data = serialize($data);
		$value = array(
			'modulename' => $post_modulename,
			'data' => $data
		);
		if(!empty($post_id)) {
			$db->update('modules', $value, "id='$post_id'");
		} else {
			$db->insert('modules', $value);
		}
		updatecache('modules');
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=modules');
	}
} elseif($get_action == 'attachments') {
	if(empty($get_id)) aexit('error');
	$attachments = '';
	$query = $db->query_by('*', 'attachments', "itemid='$get_id'", 'id');
	$i = 0;
	while($attach = $db->fetch_array($query)) {
		$id = $attach['id'];
		if(!empty($_POST)) {
			if(isset($_POST["del$id"])) {
				$db->delete('attachments', "id='$id'");
				continue;
			}
			$value = array();
			if($attach['description'] != $_POST["description$id"]) $attach['description'] = $value['description'] = $_POST["description$id"];
			if($attach['orderby'] != $_POST["orderby$id"]) $attach['orderby'] = $value['orderby'] = $_POST["orderby$id"];
			if(!empty($value)) $db->update('attachments', $value, "id='$id'");
		}
		$i ++;
		$date = date('Y-m-d', $attach['dateline']);
		$_originalname = basename($attach['filename']);
		if(!empty($attach['originalname'])) $_originalname = $attach['originalname'];
		$attachments .= "<tr><td><input name='del{$id}' value='1' type='checkbox' /></td><td>{$id}</td><td><a href=\"";
		if(strpos($attach['filename'], '://') === false) $attachments .= $homepage;
		$attachments .= "{$attach['filename']}\" target=\"_blank\">{$_originalname}</a></td><td>{$date}</td><td title=\"{$attach['description']}\"><input type='text' name='description{$id}' size='50' value='".ak_htmlspecialchars($attach['description'])."' /></td><td><input type='text' name='orderby{$id}' size='2' value='".$attach['orderby']."' /></td><td align=\"right\">".ceil($attach['filesize'] / 1024)."&nbsp;KB</td></tr>";
	}
	$db->update('items', array('attach' => $i), "id=$get_id");
	$smarty->assign('id', $get_id);
	$smarty->assign('attachments', $attachments);
	displaytemplate('admincp_attachments.htm');
	if(!empty($_POST)) {debug($lan['operatesuccess'], 0, 1);}
	aexit();
} elseif($get_action == 'selectcategories') {
	header("Cache-Control:");
	$where = "categoryup='$get_up'";
	if($get_module == -1 || $get_module == 0) {
		$where .= " AND module IN (-1,0)";
	} else {
		$where .= " AND module='$get_module'";
	}
	$query = $db->query_by('id,category', 'categories', $where);
	$i = 0;
	while($category = $db->fetch_array($query)) {
		$i ++;
		echo "$('#category{$get_level}').append(\"<option value='{$category['id']}'>{$category['category']}</option>\");\n";
	}
	if(!empty($get_defaultlist)) {
		$lists = explode(',', $get_defaultlist);
		if(isset($lists[$get_level])) {
			echo "$('#category{$get_level}').val({$lists[$get_level]});\n";
			echo "selectcategory($get_level + 1, $('#category{$get_level}').val());\n";
		}
	}
	if($i > 0) {
		echo "$(\"#category{$get_level}\").show();\n";
	} else {
		echo "$(\"#category{$get_level}\").hide();\n";
	}
} elseif($get_action == 'reviewcomment') {
	$value = array(
		'review' => $post_review,
		'reviewtime' => $thetime,
	);
	$db->update('comments', $value, "id='$post_id'");
	if(empty($post_all)) {
		debug($lan['operatesuccess'], 0, 1);
		exit("<script>document.location='index.php?file=admincp&action=comments&id={$post_itemid}';</script>");
	} else {
		adminmsg($lan['operatesuccess'], 'back', 1);
	}
} elseif($get_action == 'refreshcategory') {
	$query = $db->query_by('id', 'categories', '1', 'categoryup DESC');
	while($c = $db->fetch_array($query)) {
		refreshitemnum($c['id'], 'category');
	}
	adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=categories', 1);
} elseif($get_action == 'deletetemplate') {
	vc();
	if(substr($get_template, -4) != '.htm') aexit();
	if(strpos($get_template, '/') !== false || strpos($get_template, '\\') !== false) aexit();
	$result = unlink(AK_ROOT.'configs/templates/'.$template_path.'/'.$get_template);
	if($result) {
		adminmsg($lan['operatesuccess'], 'index.php?file=admincp&action=templates', 1);
	} else {
		adminmsg('error', 'index.php?file=admincp&action=templates', 1);
	}
} elseif($get_action == 'users') {
	$where = '1';
	$url_condition = '';
	if(!empty($get_id) && a_is_int($get_id)) {
		$where .= " AND id='$get_id'";
		$url_condition .= "&id={$get_id}";
	}
	if(!empty($get_username)) {
		$where .= " AND username='$get_username'";
		$url_condition .= "&username={$get_username}";
	}
	if(!empty($get_email)) {
		$where .= " AND email LIKE '%$get_email%'";
		$url_condition .= "&email={$get_email}";
	}
	if(!empty($get_ip)) {
		$where .= " AND ip='$get_ip'";
		$url_condition .= "&ip={$get_ip}";
	}
	if(isset($get_page)) {
		$page = $get_page;
	} else {
		$page = 1;
	}
	$page = max($page, 1);
	$ipp = 15;
	isset($post_page) && $page = $post_page;
	!a_is_int($page) && $page = 1;
	$start_id = ($page - 1) * $ipp;
	$query = $db->query_by('*', 'users', $where, 'id DESC', "$start_id,$ipp");
	$list = '';
	$url = 'index.php?file=admincp&action=users'.ak_htmlspecialchars($url_condition);
	$count = $db->get_by('COUNT(*)', 'users', $where);
	if($ipp * ($page - 1) > $count) {
		header('location:'.$currenturl.'&page='.ceil($count / $ipp));
		aexit();
	}
	$str_index = multi($count, $ipp, $page, $url);
	$smarty->assign('str_index', $str_index);
	while($user = $db->fetch_array($query)) {
		$line = "<tr><td>{$user['id']}</td><td>{$user['username']}</td><td>{$user['email']}</td><td title='".date('H:i:s', $user['createtime'])."'>".date('Y-m-d', $user['createtime'])."</td><td>{$user['ip']}</td><td align='center'><a href='index.php?file=admincp&action=resetpassword&uid={$user['id']}&vc=$vc' onclick='return confirmoperate()'>{$lan['resetpassword']}</a></td><td align='center'>";
		if(empty($user['freeze'])) {
			$line .= "{$lan['available']}(<a href='index.php?file=admincp&action=freezeuser&uid={$user['id']}&vc=$vc' onclick='return confirmoperate()'>{$lan['freeze']}</a>)";
		} else {
			$line .= "{$lan['frozen']}(<a href='index.php?file=admincp&action=unfreezeuser&uid={$user['id']}&vc=$vc' onclick='return confirmoperate()'>{$lan['activate']}</a>)";
		}
		$line .= "</td><td align='center'><a href='index.php?file=admincp&action=deleteuser&uid={$user['id']}&vc=$vc' onclick='return confirmoperate()'><span style='color:red'>{$lan['delete']}</span></a></td></tr>";
		$list .= $line;
	}
	$smarty->assign('list', $list);
	$smarty->assign('get', ak_htmlspecialchars($_GET));
	displaytemplate('admincp_users.htm');
} elseif($get_action == 'resetpassword') {
	vc();
	$newpassword = random(8);
	changeuserpassword($get_uid, $newpassword);
	adminmsg($lan['newpassis'].':'.$newpassword);
} elseif($get_action == 'freezeuser') {
	vc();
	freezeuser($get_uid, 1);
	adminmsg($lan['operatesuccess'], 'back');
} elseif($get_action == 'unfreezeuser') {
	vc();
	freezeuser($get_uid, 0);
	adminmsg($lan['operatesuccess'], 'back');
} elseif($get_action == 'deleteuser') {
	vc();
	deleteuser($get_uid);
	adminmsg($lan['operatesuccess'], 'back');
} elseif($get_action == 'paging') {
	$modules = getcache('modules');
	$count = $db->get_by('COUNT(*)', 'texts', "itemid='$get_id'");
	$query = $db->query_by('*', 'texts', "itemid='$get_id' AND page>0", 'page');
	$list = '';
	$i = 1;
	while($page = $db->fetch_array($query)) {
		$subtitle = ak_htmlspecialchars($page['subtitle']);
		if(empty($subtitle)) $subtitle = $lan['pagebreak'].$page['page'];
		$l = strlen($page['text']);
		$form = "<tr><td><div title='{$lan['contentlength']}:$l'><a href='javascript:showpage({$page['id']})'>{$i} {$subtitle}</a><a href='index.php?file=admincp&action=delpaging&id={$page['id']}&vc={$vc}' onclick='return confirmdelete()' style='float:right;'>".alert($lan['delete'])."</a></div><div id='div_{$page['id']}' style='display:none;'><form name='form_{$page['id']}' action='index.php?file=admincp&action=savepaging' method='POST'>";
		$form .= "<input type='hidden' name='id' value='{$page['id']}' />";
		$form .="{$lan['subtitle']} <input type='text' name='subtitle' style='width:300px;' value='$subtitle' /><br />";
		$form .= editor('data', $get_type, '', array('id' => "text{$page['id']}", 'uploadimgurl' => 'index.php?file=upload&id='.$get_id));
		$form .= "<input type='submit' value='{$lan['save']}' /></form></div></td></tr>";
		$list .= $form;
		$i ++;
	}
	$smarty->assign('id', $get_id);
	$smarty->assign('list', $list);
	$smarty->assign('count', $i - 1);
	$smarty->assign('newpage', editor('data', $get_type, '', array('uploadimgurl' => 'index.php?file=upload&id='.$get_id)));
	displaytemplate('paging.htm');
	aexit();
} elseif($get_action == 'savepaging') {
	$value = array(
		'subtitle' => $post_subtitle,
		'text' => $post_data
	);
	if(empty($post_id)) {
		$maxpage = getmaxpage($post_itemid);
		$value['itemid'] = $post_itemid;
		$value['page'] = $maxpage + 1;
		$db->insert('texts', $value);
		$db->update('items', array('pagenum' => '+1'), "id='{$value['itemid']}'");
	} else {
		$db->update('texts', $value, "id='$post_id'");
	}
	debug($lan['operatesuccess'], 0, 1);
	$returnurl = $_SERVER['HTTP_REFERER'];
	aexit("<script>document.location = '$returnurl';</script>");
} elseif($get_action == 'delpaging') {
	vc();
	$del_page = $db->get_by('page,itemid', 'texts', "id={$get_id}");
	$db->query("DELETE FROM ak_texts WHERE id = $get_id");
	$db->query("UPDATE {$tablepre}_texts SET page=page-1 WHERE page>{$del_page['page']} AND itemid={$del_page['itemid']}");
	$pagenum = $db->get_by('COUNT(*) AS c ', 'texts', "itemid={$del_page['itemid']} AND page>0");
	$db->update('items', array('pagenum' => $pagenum), "id={$del_page['itemid']}");
	debug($lan['operatesuccess'], 0, 1);
	$returnurl = $_SERVER['HTTP_REFERER'];
	aexit("<script>document.location = '$returnurl';</script>");
} elseif($get_action == 'ajaxtext') {
	$text = $db->get_by('text', 'texts', "id='$get_id'");
	exit($get_id.'#'.$text);
} elseif($get_action == 'ajaxcheckdomain') {
	$category = $db->get_by('*', 'categories', "domain='$get_domain'");
	if(!empty($category) && $category['id'] != $get_id) {
		aexit('error');
	}
} elseif($get_action == 'importitems') {
	if(empty($_POST)) {
		displaytemplate('importitems.htm');
	} else {
		$fields = explode(',', tidyitemlist($post_fields, ',', 0));
		$dfs = explode("\n", $post_defaultfields);
		$value = array('dateline' => $thetime);
		foreach($dfs as $df) {
			$df = trim($df);
			list($k, $v) = explode('=', $df);
			$value[$k] = $v;
		}
		$filename = $_FILES['uploaddata']['tmp_name'];
		$fp = fopen($filename, 'r');
		$j = 0;
		while(!feof($fp)) {
			$line = trim(fgets($fp));
			$values = explode("\t", $line);
			$i = 0;
			foreach($fields as $field) {
				$value[$field] = $values[$i];
				$i ++;
			}
			$value['initial'] = getinitial($value['title']);
			$db->insert('items', $value);
			$j ++;
		}
		fclose($fp);
		adminmsg($lan['importitemssuccess'].$j, 'index.php?file=admincp&action=items');
	}
} else {
	adminmsg($lan['nodefined'], '', 0, 1);
}
runinfo();
aexit();
?>
<?php
if(!defined('CORE_ROOT')) exit;
function createakcmstables($dbname) {
	global $dbtype, $db, $lan, $charset, $tablepre;
	require CORE_ROOT.'install/install.sql.php';
	if(strpos($dbtype, 'mysql') !== false) {
		if(!empty($db->error) && strpos($db->error, 'Access denied for user') !== false) adminmsg($lan['dbpwerror'], 'back', 3, 1);
		if(!empty($db->pdosecreterror)) adminmsg($lan['dbpwerror'], 'back', 3, 1);
		if(empty($db->dbexist)) {
			$createdatabasesql = 'CREATE DATABASE `'.$dbname.'`';
			if($db->version > '4.1') {
				if($charset == 'utf8') {
					$mysql_charset = ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
				} elseif($charset == 'gbk') {
					$mysql_charset = ' DEFAULT CHARACTER SET gbk COLLATE gbk_chinese_ci';
				} elseif($charset == 'english') {
					$mysql_charset = ' DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci';
				}
				$createdatabasesql = $createdatabasesql.$mysql_charset;
			}
			$db->query($createdatabasesql);
			$dbexists = 1;
			$db->close();
			$db = db(array(), 1);
		}
		$db->selectdb($dbname);
		foreach($createtablesql as $key => $value) {
			$value['charset'] = $charset;
			$createtablesql = mysql_createtable($tablepre.'_'.$key, $value);
			$_sqls = explode(";\n", $createtablesql);
			foreach($_sqls as $_sql) {
				$db->query($_sql);
			}
		}
	} elseif(strpos($dbtype, 'sqlite') !== false) {
		foreach($createtablesql as $key => $value) {
			$value['charset'] = $charset;
			$createtablesql = sqlite_createtable($tablepre.'_'.$key, $value);
			$db->query($createtablesql);
		}
	}
}

function emptyalltables() {
	global $db, $tablepre;
	require CORE_ROOT.'install/install.sql.php';
	foreach($createtablesql as $table => $_k) {
		$db->emptytable($tablepre.'_'.$table);
	}
}

function installinitialdata() {
	global $db, $lan, $charset, $tablepre;
	require CORE_ROOT.'install/install.sql.php';
	$language = 'chinese';
	foreach($insertsql as $key => $value) {
		$tablename = str_replace('ak_', $tablepre.'_', $value['tablename']);
		if($value['tablename'] == 'settings' && $value['value']['variable'] == 'language') $value['value']['value'] = $language;
		$db->insert($tablename, $value['value']);
	}
	$l = ($charset != 'utf8') ? lan($charset, $language) : $lan;
	$db->update('categories', array('category' => $l['default'].$l['space'].$l['category']), "id=1");
	$data = array();
	$data['html'] = 0;
	$data['page'] = 1;
	$data['numperpage'] = 10;
	$data['picturemaxsize'] = 999;
	$data['fields']['title'] = array('order' => 128, 'listorder' => 128, 'size' => 320);
	$data['fields']['category'] = array('order' => 100, 'listorder' => 100);
	$data['fields']['data'] = array('order' => 50, 'size' => '100%,188', 'type' => 'rich');
	$data = serialize($data);
	$value = array(
		'modulename' => $l['content'],
		'data' => $data
	);
	$db->insert('modules', $value);
	setsetting('customquickoperate', "<a href=\"index.php?action=newitem&module=1\">{$l['add']}{$l['content']}</a>\n<a href=\"http://www.akhtm.com/manual/index.htm\" target=\"_blank\">{$l['akcmsmanual']}</a>\n<a href=\"index.php?file=account&action=logout&vc=[vc]\" target=\"_parent\">{$l['logout']}</a>");
}
?>
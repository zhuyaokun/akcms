<?php
if(!defined('CORE_ROOT')) exit;
require_once(CORE_ROOT.'include/db.class.php');
class pdosqlitestuff extends dbstuff{
	var $querynum = 0;
	var $queries = array();
	var $version = '';
	var $dbname;
	var $db;
	function pdosqlitestuff($config = array()) {
		if($config['version'] == 2) {
			$this->db = new PDO("sqlite2:".AK_ROOT.$config['dbname']);
		} else {
			$this->db = new PDO("sqlite:".AK_ROOT.$config['dbname']);
		}
		$this->version = $this->version();
		$this->dbname = $config['dbname'];
		$this->db->beginTransaction();
	}
	function selectdb($dbname) {
		if($config['version'] == 2) {
			$this->db = new PDO("sqlite2:".$dbname);
		} else {
			$this->db = new PDO("sqlite:".$dbname);
		}
		$this->dbname = $dbname;
	}
	function _commit() {
		$this->db->commit();
	}
	function _fetch_array($query) {
		return $query->fetch(2);
	}
	function _query($sql) {
		$sql = str_replace('`', '', $sql);
		$sql = str_replace('ORDER BY rand()', '', $sql);
		if(trim($sql) == '') return true;
		$query = $this->db->query($sql);
		return $query;
	}
	function _close() {
		$this->commit();
		$this->db = null;
	}
	function version() {
		return $this->db->getAttribute(4);
	}
	function error() {
		$_info = $this->db->errorInfo();
		return $_info[2];
	}
	function addslashes($string) {
		return substr($this->db->quote($string), 1, -1);
	}
	function insert_id() {
		return $this->db->lastInsertId();
	}
	function getalltables() {
		$tables = array();
		$query = $this->query("SELECT * FROM sqlite_master");
		while($table = $this->fetch_array($query)) {
			if($table['type'] == 'table') $tables[] = $table['name'];
		}
		return $tables;
	}
	function getallfields($table) {
		$fields = array();
		$query = $this->query("SELECT * FROM sqlite_master WHERE name ='$table'");
		if(!$field = $this->fetch_array($query)) return false;
		$sql = $field['sql'];
		$_pos1 = strpos($sql, '(');
		if($_pos1 === false) return false;
		$sql = substr($sql, $_pos1 + 1, -1);
		$fs = explode(',', $sql);
		foreach($fs as $f) {
			if(strpos($f, 'PRIMARY KEY(') === 0) continue;
			$_pos2 = strpos($f, ' ');
			$fields[] = substr($f, 0, $_pos2);
		}
		return $fields;
	}
	function addfield($table, $field, $ext = '') {return false;}
	function affectedrows() {
		return $this->db->rowCount();
	}
	function createtable($tablename, $data) {
		$sql = sqlite_createtable($this->fulltablename($tablename), $data);
		return $this->query($sql);
	}
	function emptytable($table) {
		$this->query("delete from $table");
	}
	function gettableinfo($table) {
		$return = array();
		$query = $this->query("SELECT * FROM sqlite_master WHERE tbl_name='$table'");
		while($field = $this->fetch_array($query)) {
			if($field['type'] == 'table') {
				$sql = $field['sql'];
				$sql = str_replace("'", '', $sql);
				$_pos1 = strpos($sql, '(');
				if($_pos1 === false) return false;
				$sql = substr($sql, $_pos1 + 1, -1);
				$fs = explode(',', $sql);
				foreach($fs as $f) {
					$f = trim($f);
					if(strpos($f, 'PRIMARY KEY(') === 0) continue;
					$_pos2 = strpos($f, ' ');
					$_k = substr($f, 0, $_pos2);
					$_pos3 = strpos($f, '(');
					if(empty($_pos3)) $_pos3 = strlen($f);
					$_type = substr($f, $_pos2 + 1, $_pos3 - $_pos2 - 1);
					if($_type == 'INTEGER') $_type = 'int';
					$_length = getfield('(', ')', $f);
					if(!empty($_length) && $_type != 'text' && $_type != 'float') $_length = 255; 
					if(!empty($_length)) $return['fields'][$_k]['length'] = $_length;
					$return['fields'][$_k]['type'] = $_type;
				}
			} elseif($field['type'] == 'index') {
				$sql = trim(strtolower($field['sql']));
				if(empty($sql)) continue;
				$offset1 = strpos($sql, 'index');
				$offset2 = strpos($sql, ' ', $offset1 + 7);
				$key = substr($sql, $offset1 + 6, $offset2 - $offset1 - 6);
				$value = getfield('(', ')', $sql);
				$values = explode(',', str_replace("'", '', $value));
				$type = 'key';
				if(strpos($sql, 'unique')) $type = 'unique';
				$return['indexs'][$key]['type'] = $type;
				$return['indexs'][$key]['value'] = $values;
			}
		}
		$return['charset'] = '#';
		return $return;
	}
}
?>
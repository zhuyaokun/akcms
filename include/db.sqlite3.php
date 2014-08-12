<?php
if(!defined('CORE_ROOT')) exit;
require_once(CORE_ROOT.'include/db.class.php');
if(!class_exists('SQLite3')) exit('This server doesn\'t support SQLite3 Database');
class sqlite3stuff extends dbstuff{
	var $querynum = 0;
	var $queries = array();
	var $version = '';
	var $dbname;
	var $db;
	function sqlite3stuff($config = array()) {
		$this->db = new SQLite3(AK_ROOT.$config['dbname']);
		$this->version = $this->version();
		$this->dbname = $config['dbname'];
		$this->query("BEGIN;");
	}
	function selectdb($dbname) {
		$this->db = $this->db->open($dbname);
		$this->dbname = $dbname;
	}
	function _commit() {
		$this->query('COMMIT;');
	}
	function _fetch_array($query) {
		return $query->fetchArray(SQLITE3_ASSOC);
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
		$this->db->close();
	}
	function version() {
		return $this->db->version();
	}
	function error() {
		return $this->db->lastErrorMsg();
	}
	function addslashes($string) {
		return $this->db->escapeString($string);
	}
	function insert_id() {
		return $this->db->lastInsertRowID();
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
		return $this->db->changes();
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
					if(strpos($f, 'PRIMARY KEY(') === 0) {
						preg_match("/PRIMARY KEY\(([a-z]+)\)/is", $f, $match);
						if(isset($match[1])) {
							$key = $match[1];
							$return['indexs'][$key]['type'] = 'primary';
							$return['indexs'][$key]['value'] = $key;
							if($return['fields'][$key]['type'] == 'int') $return['fields'][$key]['auto_increment'] = 1;
						}
						continue;
					}
					$_pos2 = strpos($f, ' ');
					$_k = substr($f, 0, $_pos2);
					$_pos3 = strpos($f, '(');
					if(empty($_pos3)) $_pos3 = strlen($f);
					$_type = substr($f, $_pos2 + 1, $_pos3 - $_pos2 - 1);
					if(strpos($_type, 'INTEGER') !== false) $_type = 'int';
					if(strpos($_type, 'float') !== false) $_type = 'float';
					$_length = getfield('(', ')', $f);
					if(!empty($_length) && $_type != 'text' && $_type != 'float') $_length = 255; 
					if(!empty($_length)) $return['fields'][$_k]['length'] = $_length;
					$return['fields'][$_k]['type'] = $_type;
					preg_match("/default ([0-9]+)/is", $f, $match);
					if(isset($match[1])) $return['fields'][$_k]['default'] = $match[1];
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
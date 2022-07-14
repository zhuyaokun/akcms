<?php
if(!defined('CORE_ROOT')) exit;
require_once(CORE_ROOT.'include/db.class.php');
class sqlitestuff extends dbstuff{
	var $querynum = 0;
	var $queries = array();
	var $version = '';
	var $dbname;
	var $db;
	function sqlitestuff($config = array()) {
		$this->db = sqlite_open(AK_ROOT.$config['dbname']);
		$this->version = $this->version();
		$this->dbname = $config['dbname'];
		$this->query("BEGIN;");
	}
	function _commit() {
		$this->query('COMMIT;');
	}
	function _fetch_array($query) {
		return sqlite_fetch_array($query, SQLITE_ASSOC);
	}
	function _query($sql) {
		$sql = str_replace('`', '', $sql);
		$sql = str_replace('ORDER BY rand()', '', $sql);
		if(trim($sql) == '') return true;
		$query = sqlite_query($this->db, $sql);
		return $query;
	}
	function _close() {
		$this->commit();
		sqlite_close($this->db);
	}
	function version() {
		return sqlite_libversion();
	}
	function error() {
		return sqlite_error_string(sqlite_last_error($this->db));
	}
	function addslashes($string) {
		return sqlite_addslashes($string);
	}
	function insert_id() {
		return sqlite_last_insert_rowid($this->db);
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
		return sqlite_changes($this->db);
	}
	function createtable($tablename, $data) {
		$sql = sqlite_createtable($this->fulltablename($tablename), $data);
		return $this->query($sql);
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
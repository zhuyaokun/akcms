<?php
if(!defined('CORE_ROOT')) exit;
require_once(CORE_ROOT.'include/db.class.php');
class mysqlstuff extends dbstuff{
	var $querynum = 0;
	var $queries = array();
	var $version = '';
	var $dbname;
	var $db;
	function mysqlstuff($config = array()) {
		global $pconnect, $lan;
		if((!isset($pconnect) || $pconnect == 1) && function_exists('mysql_pconnect')) {
			if(!$connect = mysql_pconnect($config['dbhost'], $config['dbuser'], $config['dbpw'])) {
				debug($lan['connecterror'], 1);
			}
		} else {
			if(!$connect = mysql_connect($config['dbhost'], $config['dbuser'], $config['dbpw'])) {
				debug($lan['connecterror'], 1);
			}
		}
		$this->db = $connect;
		$this->charset = $config['charset'];
		$this->version = $this->version();
		$this->dbname = $config['dbname'];
		if($this->version > '4.1') mysql_query("SET NAMES '{$config['charset']}'", $connect);
		if($this->version > '5.0') mysql_query("SET sql_mode=''", $connect);
		if($config['dbname']) mysql_select_db($config['dbname'], $connect);
	}
	function _commit() {}
	function _fetch_array($query) {
		return mysql_fetch_array($query, MYSQL_ASSOC);
	}
	function _query($sql) {
		mysql_select_db($this->dbname, $this->db);
		$query = @mysql_query($sql, $this->db);
		return $query;
	}
	function _close() {
		mysql_close($this->db);
	}
	function version() {
		return mysql_get_server_info($this->db);
	}
	function error() {
		return mysql_error($this->db);
	}
	function addslashes($string) {
		return mysql_addslashes($string);
	}
	function insert_id() {
		return mysql_insert_id($this->db);
	}
	function getalltables() {
		$tables = array();
		$sql = "SHOW TABLES";
		$query = $this->query($sql);
		$tables = array();
		while($table = $this->fetch_array($query)) {
			$tables[] = current($table);
		}
		return $tables;
	}
	function getallfields($table) {
		$fields = array();
		$results = $this->querytoarray("EXPLAIN $table");
		foreach($results as $result) {
			$fields[] = $result['Field'];
		}
		return $fields;
	}
	function addfield($table, $field, $ext = '') {
		$table = $this->fulltablename($table);
		$fields = $this->getallfields($table);
		if(in_array($field, $fields)) {
			$sql = "ALTER TABLE `$table` change `$field` `$field` $ext NOT NULL";
		} else {
			$sql = "ALTER TABLE `$table` add `$field` $ext NOT NULL";
		}
		return $this->_query($sql);
	}
	function affectedrows() {
		return mysql_affected_rows($this->db);
	}
	function createtable($tablename, $data) {
		$tables = $this->getalltables();
		if(in_array($this->fulltablename($tablename), $tables)) return false;
		$sql = mysql_createtable($this->fulltablename($tablename), $data);
		return $this->query($sql);
	}
	function gettableinfo($table) {
		$return = array();
		$_query = $this->query("SHOW CREATE TABLE $table");
		$r = $this->fetch_array($_query);
		$sqls = explode("\n", $r['Create Table']);
		foreach($sqls as $sql) {
			$sql = trim($sql);
			if(substr($sql, 0, 1) == '`') {
				$_f = getfield('`', '`', $sql);
				$offset1 = strlen($_f) + 3;
				$offset2 = strpos($sql, '(', $offset1);
				if(empty($offset2)) $offset2 = strpos($sql, ' ', $offset1);
				if(empty($offset2)) $offset2 = strpos($sql, ',', $offset1);
				$type = substr($sql, $offset1, $offset2 - $offset1);
				$return['fields'][$_f]['type'] = $type;
				$length = getfield('(', ')', $sql);
				if(!empty($length)) $return['fields'][$_f]['length'] = $length;
				if(strpos(strtolower($sql), 'auto_increment')) $return['fields'][$_f]['auto_increment'] = 1;
				if(strpos(strtolower($sql), 'unsigned')) $return['fields'][$_f]['unsigned'] = 1;
			}
			unset($keytype);
			if(substr($sql, 0, 10) == 'UNIQUE KEY') {
				$keytype = 'unique';
			} elseif(substr($sql, 0, 3) == 'KEY') {
				$keytype = 'key';
			} elseif(substr($sql, 0, 11) == 'PRIMARY KEY') {
				$keytype = 'primary';
			}
			if(!empty($keytype)) {
				$_k = getfield('`', '`', $sql);
				$return['indexs'][$_k]['type'] = $keytype;
				if($keytype != 'primary') {
					$_v = getfield('(', ')', $sql);
					$_v = str_replace('`', '', $_v);
					$_v = tidyitemlist($_v, ',', 0);
					$_vs = explode(',', $_v);
					$return['indexs'][$_k]['value'] = $_vs;
				}
			}
			if(substr($sql, 0, 1) == ')') {
				$engine = getfield('ENGINE=', ' ', $sql);
				$charset = getfield('CHARSET=', '', $sql);
				if($engine == 'MEMORY') $return['engine'] = 'memory';
				$return['charset'] = $charset;
			}
		}
		return $return;
	}
}
?>
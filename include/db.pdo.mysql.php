<?php
if(!defined('CORE_ROOT')) exit;
require_once(CORE_ROOT.'include/db.class.php');
class pdomysqlstuff extends dbstuff{
	var $querynum = 0;
	var $queries = array();
	var $version = '';
	var $dbname;
	var $db;
	function pdomysqlstuff($config = array()) {
		global $currenturl, $dbexists;
		$dsn = "mysql:host={$config['dbhost']}";
		if(strpos($currenturl, 'file=install') !== false || isset($dbexist)) $dsn .= ";dbname={$config['dbname']}";
		
		try {
    			$this->db = new PDO($dsn, $config['dbuser'], $config['dbpw']);
		} catch (PDOException $e) {
			if(strpos($e->getMessage(), 'Unknown database') !== false) {
				$this->dbexist = 0;
				$dsn = "mysql:host={$config['dbhost']}";
				$this->db = new PDO($dsn, $config['dbuser'], $config['dbpw']);
				return;
			}
			if(strpos($e->getMessage(), 'YES') !== false) {
				$this->pdosecreterror = 1;
				return;
			}
			return;
		}
		$this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
		$this->version = $this->version();
		$this->dbname = $config['dbname'];
		$this->dbhost = $config['dbhost'];
		$this->dbuser = $config['dbuser'];
		$this->dbpw = $config['dbpw'];
		if($this->version > '4.1') $this->db->query("SET NAMES '{$config['charset']}'");
		if($this->version > '5.0') $this->db->query("SET sql_mode=''");
		$this->db->query("USE ".$this->dbname);
		$this->db->beginTransaction();
	}
	function selectdb($dbname) {
		$this->db->query("USE ".$dbname);
		$this->dbname = $dbname;
	}
	function _commit() {
		$this->db->commit();
	}
	function _fetch_array($query) {
		return $query->fetch(2);
	}
	function _query($sql) {
		$query = $this->db->query($sql);
		return $query;
	}
	function _close() {
		$this->db = null;
	}
	function version() {
		return $this->db->getAttribute(4);
	}
	function error() {
		$error = $this->db->errorInfo();
		return $error[2];
	}
	function addslashes($string) {
		return mysql_addslashes($string);
	}
	function insert_id() {
		return $this->db->lastInsertId();
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
		return $this->db->rowCount();
	}
	function createtable($tablename, $data) {
		if(!isset($data['charset'])) $data['charset'] = $this->charset;
		$sql = mysql_createtable($this->fulltablename($tablename), $data);
		return $this->query($sql);
	}
	function emptytable($table) {
		$this->query("TRUNCATE TABLE `$table`");
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
<?php
if(!defined('CORE_ROOT')) @include 'include/directaccess.php';
require CORE_ROOT.'include/admin.inc.php';
require CORE_ROOT.'include/task.file.func.php';
if(empty($_GET['action'])) {
} else {
	$action = $_GET['action'];
	if($action == 'export') {
		if(empty($_GET['process'])) {
			if(empty($_POST)) {
				deletetask("exportdb");
				displaytemplate('export.htm');
			} else {
				$variables = $_POST;
				$variables['packet'] = 1;
				setcache('backupdbvariables', $variables);
				extract($variables);
				if(file_exists($path."/db.ak")) exit($path.' is not empty. please clear it first.');
				$tables = $db->getalltables();
				$createtables = array();
				foreach($tables as $table) {
					if(!empty($tablecharacter) && strpos($table, $tablecharacter) === false) continue;
					if(!empty($tableskipcharacter) && strpos($table, $tableskipcharacter) !== false) continue;
					$createtables[$table] = $db->gettableinfo($table);
					$createtables[$table]['charset'] = $charset;
					$count = $db->get_field("SELECT COUNT(*) FROM {$table}");
					if($count > 0) {
						$offset = 0;
						while($offset < $count) {
							addtask("exportdb", "{$table}\t{$offset}\t{$createtables[$table]['charset']}");
							$offset += $step;
						}
					}
				}
				$createtables = serialize($createtables);
				writetofile($createtables, $path."/db.ak");
				msgbox('', 'index.php?file=db&action=export&process=1');
			}
		} else {
			$variables = getcache('backupdbvariables');
			extract($variables);
			$task = gettask("exportdb");
			if(empty($task)) {
				updatecache();
				msgbox($lan['operatesuccess'], '');
			}
			$task = trim($task);
			list($table, $start, $charset) = explode("\t", $task);
			$query = $db->query("SELECT * FROM {$table} LIMIT $start,$step");
			while($row = $db->fetch_array($query)) {
				$_row = array(
					'table' => $table,
					'value' => $row
				);
				$_row = base64_encode(serialize($_row));
				aklog($_row, $path."/db-{$packet}.ak");
			}
			if(filesize($path."/db-{$packet}.ak") > $volume * 1024) {
				$variables['packet'] ++;
				setcache('backupdbvariables', $variables);
			}
			msgbox($lan['execution'], 'index.php?file=db&action=export&process=1');
		}
	} else {//import
		if(empty($_GET['process'])) {
			if(empty($_POST)) {
				deletetask("importdb");
				displaytemplate('import.htm');
			} else {
				$variables = $_POST;
				extract($variables);
				setcache('restoredbvariables', $variables);
				$createtables = readfromfile($path."/db.ak");
				$createtables = unserialize($createtables);
				$tables = $db->getalltables();
				foreach($createtables as $table => $data) {
					if(in_array($table, $tables)) runquery("DROP TABLE {$table}");
					if(strpos($dbtype, 'mysql') !== false) {
						$sql = mysql_createtable($table, $data);
					} else {
						$sql = sqlite_createtable($table, $data);
					}
					runquery($sql);
				}
				$i = 1;
				while(1) {
					if(!file_exists($path."/db-{$i}.ak")) break;
					$fp = fopen($path."/db-{$i}.ak", 'r');
					$j = 0;
					$start = 0;
					while(!feof($fp)) {
						$_line = fgets($fp);
						if(strlen($_line) == 1023) continue;
						$j ++;
						if($j > $step) {
							$offset = ftell($fp);
							addtask("importdb", "{$i}\t{$start}\t{$offset}");
							$start = $offset;
							$j = 0;
						}
					}
					addtask("importdb", "{$i}\t{$start}\t".filesize($path."/db-{$i}.ak"));
					fclose($fp);
					$i ++;
				}
				msgbox('', 'index.php?file=db&action=import&process=1');
			}
		} else {
			$variables = getcache('restoredbvariables');
			extract($variables);
			$task = gettask("importdb");
			if(empty($task)) {
				updatecache();
				msgbox($lan['operatesuccess'], '');
			}
			list($id, $start, $end) = explode("\t", $task);
			$fp = fopen($path."/db-{$id}.ak", 'r');
			fseek($fp, $start);
			$line = '';
			while(!feof($fp)) {
				$_line = fgets($fp, 1024);
				$line .= $_line;
				if(strlen($_line) == 1023) continue;
				if(empty($line)) continue;
				$line = base64_decode($line);
				$value = unserialize($line);
				$line = '';
				$db->insert($value['table'], $value['value']);
				if($end != 0 && ftell($fp) >= $end) break;
			}
			fclose($fp);
			msgbox($lan['execution'], 'index.php?file=db&action=import&process=1');
		}
	}
}

function msgbox($message, $target = '') {
	echo "<h3>$message</h3>";
	if(!empty($target)) echo "<script>function go(){document.location=\"$target\";}setTimeout(\"go()\", 1);</script>";
	aexit();
}
?>
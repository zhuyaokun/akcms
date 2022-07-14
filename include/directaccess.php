<?php
$file = substr(basename($_SERVER['SCRIPT_FILENAME']), 0, -4);
$_GET['file'] = $file;
$url = http_build_query($_GET);
header('location:index.php?'.$url);
exit;
?>
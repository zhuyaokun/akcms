<?phpif(!defined('CORE_ROOT')) exit;
$sysname = 'AKCMS';
$sysedition = '4.2.1';
$authkey = 'xianshiqi';
$itemfields = array('title','shorttitle','aimurl','filename','category','section','template','price','author','source','dateline','pageview','picture','attach','comment','keywords','digest','data','paging','orderby','orderby2','orderby3','orderby4','orderby5','orderby6','orderby7','orderby8');
$IIIIIIIIIIII = AK_ROOT.'configs/auth.php';
$IIIIIIIIIIIl = AK_ROOT.'configs/auth.lst';
$authsuccess = 0;
if($__callmode == 'web') $_vc = ak_md5($_SERVER['HTTP_USER_AGENT']."\t".$_SERVER['REMOTE_ADDR'],1);
if(file_exists($IIIIIIIIIIII)) {
$IIIIIIIIIII1 = readfromfile($IIIIIIIIIIII);
$IIIIIIIIIII1 = decodeauth($IIIIIIIIIII1);
if($IIIIIIIIIII1 != '') {
if(strpos($IIIIIIIIIII1,'#') !== false) {
$IIIIIIIIIIll = substr($IIIIIIIIIII1,0,-11);
$IIIIIIIIII1I = substr($IIIIIIIIIII1,-10);
if($IIIIIIIIII1I <$IIIIIIIIII1l) {
unlink($IIIIIIIIIIII);
}else {
if($__callmode != 'web') {
$authsuccess = 1;
}else {
if(!empty($_SERVER['SERVER_ADDR']) &&$IIIIIIIIIIll == $_SERVER['SERVER_ADDR']) $authsuccess = 1;
if($IIIIIIIIIIll == $_SERVER['HTTP_HOST']) $authsuccess = 1;
if(strlen($_SERVER['HTTP_HOST']) > strlen($IIIIIIIIIIll) &&substr($_SERVER['HTTP_HOST'],strlen($IIIIIIIIIIll) * -1) == $IIIIIIIIIIll) $authsuccess = 1;
}
}
}else {
$IIIIIIIIIlII = explode("\t",$IIIIIIIIIII1);
foreach($IIIIIIIIIlII as $IIIIIIIIIII1) {
if($__callmode != 'web') {
$authsuccess = 1;
break;
}
if(!empty($_SERVER['SERVER_ADDR']) &&$IIIIIIIIIII1 == $_SERVER['SERVER_ADDR']) $authsuccess = 1;
if($IIIIIIIIIII1 == $_SERVER['HTTP_HOST']) $authsuccess = 1;
if(strlen($_SERVER['HTTP_HOST']) >strlen($IIIIIIIIIII1) &&substr($_SERVER['HTTP_HOST'],strlen($IIIIIIIIIII1) * -1) == $IIIIIIIIIII1) $authsuccess = 1;
if($authsuccess == 1) break;
}
}
}
if($IIIIIIIIIII1 == ''&&empty($IIIIIIIIIlI1)) {
if(file_exists($IIIIIIIIIIIl)) {
$IIIIIIIIIllI = readfromfile($IIIIIIIIIIIl);
$IIIIIIIIIllI = str_replace("\r\n","\n",$IIIIIIIIIllI);
$IIIIIIIIIlll = explode("\n",$IIIIIIIIIllI);
}else {
$IIIIIIIIIlll = array($_SERVER['SERVER_ADDR'],$_SERVER['HTTP_HOST']);
}
$IIIIIIIIIlll = implode(',',$IIIIIIIIIlll);
$IIIIIIIIIl1I = 'http://auth.akhtm.com/getauth.php?version='.$sysedition.'&key='.$IIIIIIIIIlll;
$IIIIIIIIIl1l = readfromurl($IIIIIIIIIl1I);
if(substr($IIIIIIIIIl1l,0,5) == '<?php'&&strlen($IIIIIIIIIl1l) >52) {
writetofile($IIIIIIIIIl1l,$IIIIIIIIIIII);
exit('auth file updated!please refresh!');
}else {
@unlink($IIIIIIIIIIII);
exit('auth file update error,please download auth file from <a href="http://auth.akhtm.com/" target="_blank">http://auth.akhtm.com/</a>');
}
}
}
unset($authkey,$IIIIIIIIIII1,$IIIIIIIIIlII,$IIIIIIIIIIII,$IIIIIIIIIIIl,$IIIIIIIIII1I);
function decodeauth($IIIIIIIII1II) {
$IIIIIIIII1Il = substr($IIIIIIIII1II,39,-2);
$IIIIIIIII1I1 = substr($IIIIIIIII1II,7,32);
$IIIIIIIII1II = base64_decode($IIIIIIIII1Il);
$IIIIIIIII1II = ak_xor($IIIIIIIII1II,$GLOBALS['authkey']);
if(md5($IIIIIIIII1II) != $IIIIIIIII1I1) $IIIIIIIII1II = '';
return $IIIIIIIII1II;
}
function renderdata($IIIIIIIII1l1,$IIIIIIIII11I) {
$IIIIIIIII11l = '';
$IIIIIIIII111 = array();
$IIIIIIIIlIII = array();
foreach($IIIIIIIII1l1 as $IIIIIIIIlIIl) {
$IIIIIIIIlIII = array_merge($IIIIIIIIlIII,$IIIIIIIIlIIl);
}
if(count($IIIIIIIII1l1) >0) $IIIIIIIIlIll = array_keys($IIIIIIIIlIII);
if(count($IIIIIIIIlIll) == 0) return $IIIIIIIII11I['emptymessage'];
foreach($IIIIIIIIlIll as $IIIIIIIIlI1I) {
$IIIIIIIII111[$IIIIIIIIlI1I] = "[$IIIIIIIIlI1I]";
}
$IIIIIIIIlI1l = 0;
preg_match_all('/\[%?(_[\w_-]{1,99})\]/',$IIIIIIIII11I['template'],$IIIIIIIIllII,PREG_SET_ORDER);
foreach($IIIIIIIII1l1 as $IIIIIIIIllIl =>$IIIIIIIIllI1) {
$IIIIIIIIlllI = $IIIIIIIII11I['template'];
foreach($IIIIIIIIllII as $IIIIIIIIllll) {
$IIIIIIIIlll1 = $IIIIIIIIllll[1];
if(!isset($IIIIIIIIllI1[$IIIIIIIIlll1])) {
$IIIIIIIIlllI = preg_replace("/\[%?{$IIIIIIIIlll1}(:[0-9a-z]+)?\]/s",'',$IIIIIIIIlllI);
}
}
$IIIIIIIIlllI = recursiontemplate($IIIIIIIII11I,$IIIIIIIIllI1,$IIIIIIIIlllI);
foreach($IIIIIIIIlIll as $IIIIIIIIlI1I) {
$IIIIIIIIll1l = getfield("[$IIIIIIIIlI1I:",']',$IIIIIIIIlllI);
if(!empty($IIIIIIIIll1l)) {
$IIIIIIIII111["$IIIIIIIIlI1I:$IIIIIIIIll1l"] = "[$IIIIIIIIlI1I:$IIIIIIIIll1l]";
if(a_is_int($IIIIIIIIll1l)) {
$IIIIIIIIll11 = htmltotext($IIIIIIIIllI1[$IIIIIIIIlI1I]);
$IIIIIIIIllI1["$IIIIIIIIlI1I:$IIIIIIIIll1l"] = ak_substr($IIIIIIIIll11,0,$IIIIIIIIll1l);
}
if($IIIIIIIIll1l == 'text') {
$IIIIIIIIllI1["$IIIIIIIIlI1I:$IIIIIIIIll1l"] = htmltotext($IIIIIIIIllI1[$IIIIIIIIlI1I]);
}
if(!isset($IIIIIIIIllI1["$IIIIIIIIlI1I:$IIIIIIIIll1l"])) $IIIIIIIIllI1["$IIIIIIIIlI1I:$IIIIIIIIll1l"] = '';
}
}
$IIIIIIIII11l .= ak_array_replace($IIIIIIIII111,$IIIIIIIIllI1,$IIIIIIIIlllI);
$IIIIIIIIlI1l ++;
if(isset($IIIIIIIII11I['colspan']) &&$IIIIIIIII11I['colspan'] >0) {
if($IIIIIIIIlI1l %$IIIIIIIII11I['colspan'] == 0 &&isset($IIIIIIIII1l1[$IIIIIIIIllIl +1])) $IIIIIIIII11l .= $IIIIIIIII11I['overflow'];
}
}
$IIIIIIIII11l = preg_replace( '/\[%?_[\w_-]{1,99}\]/','',$IIIIIIIII11l);
if(!empty($IIIIIIIII11I['filter'])) $IIIIIIIII11l = filter($IIIIIIIII11I['filter'],$IIIIIIIII11l);
return $IIIIIIIII11l;
}
function renderhtml($IIIIIIIIl1Il,$IIIIIIIIl1I1) {
global $lr,$homepage,$setting_forbidstat,$authsuccess,$currenturl;
if(strpos($IIIIIIIIl1Il,'<!--filter:') !== false) {
$IIIIIIIIl1lI = substr($IIIIIIIIl1Il,-20);
preg_match("/<!--filter:([0-9]+)-->/is",$IIIIIIIIl1lI,$IIIIIIIIl1l1);
if(!empty($IIIIIIIIl1l1)) {
$IIIIIIIIl11I = $IIIIIIIIl1l1[1];
$IIIIIIIIl1Il = filter($IIIIIIIIl11I,$IIIIIIIIl1Il);
$IIIIIIIIl1Il = str_replace("<!--filter:{$IIIIIIIIl11I}-->",'',$IIIIIIIIl1Il);
}
}
if(isset($_SERVER['REQUEST_URI']) &&strlen($_SERVER['REQUEST_URI']) >4 &&substr($_SERVER['REQUEST_URI'],-4) == '.xml') $IIIIIIIIl11l = 1;
if(!empty($IIIIIIIIl1I1['htmlfilename']) &&substr($IIIIIIIIl1I1['htmlfilename'],-4) == '.xml') $IIIIIIIIl11l = 1;
if(strlen($IIIIIIIIl1Il) >5 &&substr($IIIIIIIIl1Il,0,5) == '<?xml') $IIIIIIIIl11l = 1;
if(empty($authsuccess) &&empty($IIIIIIIIl11l)) {
if(strpos($IIIIIIIIl1Il,'[powered]') === false) {
$IIIIIIIIl1Il = preg_replace('/<\/body>/i',"[powered]{$lr}</body>",$IIIIIIIIl1Il);
}
if(strpos($IIIIIIIIl1Il,'[powered]') === false &&strpos($IIIIIIIIl1Il,'<') !== false) $IIIIIIIIl1Il .= "[powered]";
}
if(empty($setting_forbidstat)) {
if(strpos($IIIIIIIIl1Il,'[inc]') === false) {
$IIIIIIIIl1Il = preg_replace('/<\/body>/i',"[inc]{$lr}</body>",$IIIIIIIIl1Il);
}
}
if(!empty($IIIIIIIIl1I1['_pageid'])) {
$IIIIIIIIllIl = $IIIIIIIIl1I1['_pageid'];
$IIIIIIIIl111 = $IIIIIIIIl1I1['_pagetype'];
$IIIIIIII1III = getinc($IIIIIIIIllIl,$IIIIIIIIl111);
}else {
$IIIIIIII1III = '';
}
$IIIIIIIIl1Il = ak_replace('[inc]',$IIIIIIII1III,$IIIIIIIIl1Il);
$IIIIIIII1IIl = '';
if(empty($authsuccess)) $IIIIIIII1IIl = "";//"<!--akcms--><span id='poweredakcms'>Powered by <a href='http://www.akhtm.com' target='_blank'>AKCMS</a></span><script type='text/javascript'>if(isVisible(document.getElementById('poweredakcms'))== false) {var html_doc = document.getElementsByTagName('head')[0];var s = document.createElement(\"script\");s.src = \"http://s.akhtm.com/p.js?r=".random(6)."\";html_doc.appendChild(s);} function isVisible(obj){try{obj.focus();}catch(e){return false;}return true;}</script>";
$IIIIIIIIl1Il = ak_replace('[*home*]',$homepage,$IIIIIIIIl1Il);
$IIIIIIIIl1Il = ak_replace('[powered]',$IIIIIIII1IIl,$IIIIIIIIl1Il);
$IIIIIIIIl1Il = ak_replace('[n]',"\n",$IIIIIIIIl1Il);
if(substr($IIIIIIIIl1Il,0,17) == '<!--clearspace-->') $IIIIIIIIl1Il = clearhtml(substr($IIIIIIIIl1Il,17));
return $IIIIIIIIl1Il;
}
function getinc($IIIIIIIIllIl = 0,$IIIIIIIIl111 = 'item') {
if($IIIIIIIIllIl == 0) return '';
if($IIIIIIIIl111 == 'category') $IIIIIIIIllIl = 'c'.$IIIIIIIIllIl;
$IIIIIIII1IlI = "<img style='display:none;' alt='' src='[*home*]akcms_inc.php?i={$IIIIIIIIllIl}' />";
return $IIIIIIII1IlI;
}
function getcopyrightinfo() {
return "";//<center class='mininum' style='margin-top:5px;'><a href='http://www.akhtm.com/' target='_blank'>Copyright &copy; 2007-2012 {$GLOBALS['sysname']}&nbsp;{$GLOBALS['sysedition']}</a></center>";
}?>
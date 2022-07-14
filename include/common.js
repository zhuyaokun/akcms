function $(obj) {
	return document.getElementById(obj);
}
function ajaxget(ajaxurl, recallfunction) {
	xmlHttp.open("GET", ajaxurl, true);
	xmlHttp.onreadystatechange = eval(recallfunction);
	xmlHttp.send(null);
}
(function($){$.fn.html5Uploader=function(options){var crlf='\r\n';var boundary="akcms";var dashes="--";var settings={"name":"uploadedFile","postUrl":"","onClientAbort":null,"onClientError":null,"onClientLoad":null,"onClientLoadEnd":null,"onClientLoadStart":null,"onClientProgress":null,"onServerAbort":null,"onServerError":null,"onServerLoad":null,"onServerLoadStart":null,"onServerProgress":null,"onServerReadyStateChange":null};if(options){$.extend(settings,options);}
return this.each(function(options){var $this=$(this);if($this.is("[type='file']")){$this
.bind("change",function(){if(this.files) {var files=this.files;for(var i=0;i<files.length;i++){fileHandler(files[i]);}} });}else{$this
.bind("dragenter dragover",function(){return false;})
.bind("drop",function(e){var files=e.originalEvent.dataTransfer.files;for(var i=0;i<files.length;i++){fileHandler(files[i]);}
return false;});}});function fileHandler(file){var fileReader=new FileReader();fileReader.onabort=function(e){if(settings.onClientAbort){settings.onClientAbort(e,file);}};fileReader.onerror=function(e){if(settings.onClientError){settings.onClientError(e,file);}};fileReader.onload=function(e){if(settings.onClientLoad){settings.onClientLoad(e,file);}};fileReader.onloadend=function(e){if(settings.onClientLoadEnd){settings.onClientLoadEnd(e,file);}};fileReader.onloadstart=function(e){if(settings.onClientLoadStart){settings.onClientLoadStart(e,file);}};fileReader.onprogress=function(e){if(settings.onClientProgress){settings.onClientProgress(e,file);}};fileReader.readAsDataURL(file);var xmlHttpRequest=new XMLHttpRequest();xmlHttpRequest.upload.onabort=function(e){if(settings.onServerAbort){settings.onServerAbort(e,file);}};xmlHttpRequest.upload.onerror=function(e){if(settings.onServerError){settings.onServerError(e,file);}};xmlHttpRequest.upload.onload=function(e){if(settings.onServerLoad){settings.onServerLoad(e,file);}};xmlHttpRequest.upload.onloadstart=function(e){if(settings.onServerLoadStart){settings.onServerLoadStart(e,file);}};xmlHttpRequest.upload.onprogress=function(e){if(settings.onServerProgress){settings.onServerProgress(e,file);}};xmlHttpRequest.onreadystatechange=function(e){if(settings.onServerReadyStateChange){settings.onServerReadyStateChange(e,file);}};xmlHttpRequest.open("POST",settings.postUrl,true);if(file.getAsBinary){var data=dashes+boundary+crlf+
"Content-Disposition: form-data;"+
"name=\""+settings.name+"\";"+
"filename=\""+unescape(encodeURIComponent(file.name))+"\""+crlf+
"Content-Type: application/octet-stream"+crlf+crlf+
file.getAsBinary()+crlf+
dashes+boundary+dashes;xmlHttpRequest.setRequestHeader("Content-Type","multipart/form-data;boundary="+boundary);xmlHttpRequest.sendAsBinary(data);}else if(window.FormData){var formData=new FormData();formData.append(settings.name,file);xmlHttpRequest.send(formData);}}};})(jQuery);

function currentmenu(nav, menu) {
	var parentdoc = $(window.parent.document);
	parentdoc.find("li").removeClass("current");
	parentdoc.find("#"+nav).addClass("current");
	parentdoc.find("#"+menu).addClass("current");
}

function ajaxtip(t) {
	$("body").append("<div id='ajaxtipbox'><div>"+t+"</div></div>");
	var ajaxtipbox = $("#ajaxtipbox");
	ajaxtipbox.css("opacity", "0").show();
	var window_w = $(window).width();
	var window_h = $(window).height();
	var tip_w = ajaxtipbox.width();
	var tip_h = ajaxtipbox.height();
	var p_left = Math.floor((window_w - tip_w)/2);
	var p_top = Math.floor((window_h - tip_h)/2) + $(window).scrollTop();
	ajaxtipbox.css({ top: p_top, left: p_left});
	function slowhide() {
		var timeout = 200;
		ajaxtipbox.animate({opacity: "0", top: p_top - 50}, timeout);
		setTimeout(function(){ajaxtipbox.remove()}, timeout);
	}
	ajaxtipbox.animate({opacity: '1'}, 200, function(){
		setTimeout(slowhide, Math.max(t.length * 50, 999));
	});
}

function zebra(obj) {
	obj.find("tr").filter(":even").not(".header").children("td").css("background", "F8F8F8");
	obj.find("tr").filter(":odd").not(".header").children("td").css("background", "#EFEFEF");
}

function popup(url) {
	//弹出浮动窗体
	var iframehtml = "<div id='popup_div' style='position:absolute;left:-10000px;z-index:100;border:6px solid #DDDDDD;'><iframe id='popup_iframe' frameborder='0' scrolling='no'></iframe><a id='popup_close' style='float:right;display:block;position:absolute;width:26px;height:26px;top:0px;right:0px;background:url(images/admin/close.gif);background-repeat:no-repeat;background-position:center;' href='#'></a></div>";
	$("body").append(iframehtml);
	$("#popup_iframe").attr("src", url);
	$("#popup_iframe").load(function(){
		var height = $(this).contents().find("body").height();
		var width = $(this).contents().find("body").width();
		$(this).height(height);
		$(this).width(width);
		var windowWidth = $(window).width();
		var windowHeight = $(window).height();
		var popupHeight = height;
		var popupWidth = width;
		$("#popup_div").css({
			"top": 18,
			"left": windowWidth/2-popupWidth/2,
			"width": width,
			"height": height,
			"display":"block"
		});
	});

	$("#popup_close").live('click' ,function(){
		$("#popup_div").remove();
	});
}

function safeloadiframe(cpurl, iframeid, partentid, relativeid, relativeclass) {
	//此方法存在硬编码，不够通用，应改进
	if(relativeid == undefined) relativeid = null;
	if(relativeclass == undefined) relativeclass = null;
	var getscript = function() {
		$.getScript('http://s.akhtm.com/js/isonline.js?'+Math.random(), function(data){
			if(window.online !== undefined) {
				loadiframe();
			}
		});
	}
	setTimeout(getscript, 100);
	function loadiframe() {
		$("#"+relativeid).addClass(relativeclass);
		$("#"+iframeid).attr("src", cpurl);
		$("#"+partentid).css("display", "block");
		resizewindow();
	}
	function resizewindow() {
		var leftinfo = $(window).width()-$("#rightinfo").outerWidth() - 7;
		$("#leftinfo").css("width", leftinfo);
	}
	$(window).resize(function() {
		resizewindow();
	});
}

//串行请求
function serialrequest(params, callback){
	//指定默认请求方式为GET
	if(params == '' || params === undefined) return false;
	var requesttype = 'GET';
	var cobj = params.shift();
	if(cobj === undefined) return;
	if(cobj['api'] == '') serialrequest(params);
	if(cobj['postdata'] !== undefined) requesttype = "POST";
	$.ajax({
		url : cobj['api'],
		type: requesttype,
		dataType: "text",
		success : function(data) {
			if(typeof(callback) == 'function') {
				var result = callback.call(this, data, cobj['id']);
				if(result == 'fail') return; 
			}
			serialrequest(params, callback);
		},
		data : cobj['postdata']
	});
}

function pageWidth() {
	//获得当前document宽度
	if($.browser.msie) {
		return document.compatMode == "CSS1Compat" ? document.documentElement.clientWidth : document.body.clientWidth;
	} else {
		return self.innerWidth;
	}
}

function pageHeight() {
	//获得当前document高度
	if($.browser.msie) {
		return document.compatMode == "CSS1Compat" ? document.documentElement.clientHeight : document.body.clientHeight;
	} else {
		return self.innerHeight;
	}
}

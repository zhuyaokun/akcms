akcms
=====

chmod -Rf 777 www.xxxx.com

nginx conf

	location / {
		try_files $uri $uri/ =404;
		rewrite ^/search/(.+)$ /search.php?id=38&s=$1&file=category last;
	}

这个版本是馒头辛苦修改的，能支持 PHP 7+
这个版本只支持sqlite3，不支持mysql

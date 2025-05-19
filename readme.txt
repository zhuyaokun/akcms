chmod -Rf 777 www.dbmailserver.com

nginx conf

	location / {
		try_files $uri $uri/ =404;
		rewrite ^/search/(.+)$ /baidu/search.php?id=38&s=$1&file=category last;
	}
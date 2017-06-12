<?php 
	$address="127.0.0.1";
	$port="5000";
	$msg=json_encode(Array(
		'classificar',
		PHP_INT_MAX
	));
	var_dump($msg);

	$sock=socket_create(AF_INET,SOCK_STREAM,0) or die("Cannot create a socket");
	socket_connect($sock,$address,$port) or die("Could not connect to the socket");
	socket_write($sock,$msg);
	$buf = socket_read($sock, 1024);
	socket_close($sock);
	
    var_dump($buf);

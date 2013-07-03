<?php

$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);

socket_connect($socket, "/tmp/naspi/pd/pd.sock");

$msg = "\nThis is a test\r\n";

socket_write($socket, $msg, strlen($msg));

for($i = 0; $i < 50; $i++)
{
	$letters = "abcdefghijklmnopqrstuvwxyz1234567890";
	$ranLen = rand(10, 200);
	
	
	$rstr = "";
	$loopcnt = 0;
	for($elet = 0; $elet < $ranLen; $elet++)
	{
		$rstr = $rstr.substr($letters, rand(0, strlen($letters) - 1), 1);
		$loopcnt++;
		
		if($loopcnt == 4) { $rstr = $rstr." "; $loopcnt = 0; }
	}
	$msg = "mod addons ".trim($rstr)."\n";
	$msg = "mod addons list\n";
	socket_write($socket, $msg, strlen($msg));
	
	$buffer = socket_read($socket, 2048, PHP_NORMAL_READ);
	if(strlen($buffer) == 0) { break; }
	else if($buffer === false) { break; }
	
	echo trim($buffer)."\n";
	
	sleep(6);
}

$msg = "die\n";
socket_write($socket, $msg, strlen($msg));

socket_close($socket);

?>

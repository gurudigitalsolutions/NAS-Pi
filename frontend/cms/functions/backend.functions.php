<?///////////////////////////////////////////////////////////////////////////////////
//
//	NAS-Pi Daemon Communication Functions
//
//	These functions allow modules to communicate with the NAS-Pi daemon.
//
/////////////////////////////////////////////////////////////////////////////////////

function DaemonModuleCommand($modcode, $command)
{
	return DaemonRawCommand("mod ".$modcode." ".$command);
}

function DaemonRawCommand($command)
{
	$command = trim($command)."\n";
	$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
	
	if($socket === false) { return "FAIL Could not create socket."; }
	socket_connect($socket, "/tmp/naspi/pd/pd.sock");
	
	socket_write($socket, $command, strlen($command));
	
	$toret = "";
	/*$KeepReading = true;
	while($KeepReading)
	{
		$buffer = socket_read($socket, 2048, PHP_NORMAL_READ);
		if(strlen($buffer) == 0) { $KeepReading = false; }
		else if($buffer === false) { $KeepReading = false; }
		else { $toret = $toret.$buffer; }
	}*/
	
	$buffer = socket_read($socket, 2048, PHP_NORMAL_READ);
	$toret = trim($buffer);
	
	socket_close($socket);
	return $toret;
}





?>

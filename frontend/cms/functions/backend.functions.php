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
	$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
	socket_connect($socket, "/tmp/naspi/pd/pd.sock");
	
	socket_write($socket, $command, strlen($command));
	
	$toret = "";
	$KeepReading = true;
	while($KeepReading)
	{
		$buffer = socket_read($socket, 2048, PHP_NORMAL_READ);
		if(strlen($buffer) == 0) { break; }
		else if($buffer === false) { break; }
		
		$toret = $toret.$buffer;
	}
	
	socket_close($socket);
	return $toret;
}





?>

<?//	NAS-Pi Module pitunes modPiTunes
/////////////////////////////////////////////////////////////////////////////////////
//
//	This class is for a music player.
//
/////////////////////////////////////////////////////////////////////////////////////

class modPiTunes extends BackendModule
{
	
	
	function Initialize()
	{
		$this->Code = "pitunes";
		$this->Version = "0.2013.07.10";
		$this->Author = "Brian Murphy";
		$this->ModuleTitle = "PiTunes";
		$this->Description = "Remote controlled music player.";
	}
	
	function ProcessCommand($arguments)
	{
		//	$arguments is an array of the arguments sent to the daemon from the
		//	frontend.
		
		global $ModulesPath;
		$NeedResponse = false;
		
		if(count($arguments) < 1) { return "FAIL No arguments given to do anything."; }
		
		if($arguments[0] == "::") { $NeedResponse = true; }
		$argline = "";
		foreach($arguments as $earg)
		{
			$argline = $argline." ".$earg;
		}
		$argline = trim($argline);
		if(substr($argline, 0, 3) == ":: ") { $argline = substr($argline, 3); }
		
		
		
		return $this->TunesCommand($argline, $NeedResponse);
	}
	
	function TunesCommand($command, $needresponse)
	{
		$command = trim($command)."\n";
		$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
		
		if($socket === false) { return "FAIL Could not create socket."; }
		socket_connect($socket, "/tmp/naspi/pitunes/pitunes.sock");
		
		socket_write($socket, $command, strlen($command));
		
		if(!$needresponse)
		{
			usleep(100000);
			socket_close($socket);
			return ":)";
		}
		
		$toret = "";
		/*$KeepReading = true;
		while($KeepReading)
		{
			$buffer = socket_read($socket, 2048, PHP_NORMAL_READ);
			if(strlen($buffer) == 0) { $KeepReading = false; }
			else if($buffer === false) { $KeepReading = false; }
			else { $toret = $toret.$buffer; }
		}*/
		
		$buffer = socket_read($socket, 4096, PHP_NORMAL_READ);
		$toret = trim($buffer);
		
		socket_close($socket);
		
		return $toret;
	}
	
}

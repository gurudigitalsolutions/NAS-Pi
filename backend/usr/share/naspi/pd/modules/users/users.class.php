<?//	NAS-Pi Module users modUsers
/////////////////////////////////////////////////////////////////////////////////////
//
//	This class is an example of how to call a bash script as part of a backend
//	operation in NAS-Pi
//
/////////////////////////////////////////////////////////////////////////////////////

class modUsers extends BackendModule
{
	public $BashScript = "users.sh";
	
	function Initialize()
	{
		$this->Code = "users";
		$this->Version = "0.2013.08.18";
		$this->Author = "Brian Murphy";
		$this->ModuleTitle = "Users";
		$this->Description = "User management backend.";
	}
	
	function ProcessCommand($arguments)
	{
		//	$arguments is an array of the arguments sent to the daemon from the
		//	frontend.
		
		global $ModulesPath;
		
		if(count($arguments) == 0) { return "FAIL No action was specified"; }
		
		$argstring = "";
		foreach($arguments as $earg)
		{
			$argstring = $argstring." ".$earg;
		}
		$argstring = trim($argstring);
		
		$cmd = $ModulesPath."/".$this->Code."/".$this->BashScript." ".$argstring;
		
		return `$cmd`;
	}
	
}

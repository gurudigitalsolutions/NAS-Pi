<?//	NAS-Pi Module bashexample modBashExample
/////////////////////////////////////////////////////////////////////////////////////
//
//	This class is an example of how to call a bash script as part of a backend
//	operation in NAS-Pi
//
/////////////////////////////////////////////////////////////////////////////////////

class modBashExample extends BackendModule
{
	public $BashScript = "bashexample.sh";
	
	function Initialize()
	{
		$this->Code = "bashexample";
		$this->Version = "0.2013.07.08";
		$this->Author = "Brian Murphy";
		$this->ModuleTitle = "Bash Example";
		$this->Description = "An example of how to call a bash script from the backend of NAS-Pi.";
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

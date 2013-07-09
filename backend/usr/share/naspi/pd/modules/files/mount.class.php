<?//	NAS-Pi Module files modMount
/////////////////////////////////////////////////////////////////////////////////////
//
//	Configures and creates fstab entry and attempts to mount the share/source
//	
//
/////////////////////////////////////////////////////////////////////////////////////

class modMount extends BackendModule
{
	public $BashScript = "mount.sh";
	
	function Initialize()
	{
		$this->Code = "files";
		$this->Version = "0.2013.07.08";
		$this->Author = "Chad Gould";
		$this->ModuleTitle = "Mount Shares";
		$this->Description = "Configures and creates fstab entry and attempts to mount the share/source";
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

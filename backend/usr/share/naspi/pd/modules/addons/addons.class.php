<?//	NAS-Pi Module addons modAddons
/////////////////////////////////////////////////////////////////////////////////////
//
//	This class is the back-end support for the addons module.  It will handle all of
//	the installing and uninstalling and crap.
//
/////////////////////////////////////////////////////////////////////////////////////

class modAddons extends BackendModule
{
	function Initialize()
	{
		$this->Code = "addons";
		$this->Version = "0.2013.07.02";
		$this->Author = "Brian Murphy";
		$this->ModuleTitle = "Add-On Modules";
		$this->Description = "Seamlessly manage new features from the repository.";
	}
	
	function ProcessCommand($arguments)
	{
		//	Gotta figure out what it's gonna do
		//
		//	TODO - this :)
		
		if(count($arguments) == 0) { return "FAIL No action was specified"; }
		
		if($arguments[0] == "list") { return $this->ListInstalledModules(); }
		return ":) Process successfully completed";
	}
	
	function ListInstalledModules()
	{
		$toret = "";
		$drs = scandir(MODULECONFIGPATH);
		
		foreach($drs as $edir)
		{
			if($edir != "." && $edir != "..")
			{
				$toret = $toret." ".$edir;
			}
		}
		
		return trim($toret);
	}
}

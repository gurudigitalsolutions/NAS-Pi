<?//	NAS-Pi Module addons modAddons
/////////////////////////////////////////////////////////////////////////////////////
//
//	This class is the back-end support for the addons module.  It will handle all of
//	the installing and uninstalling and crap.
//
/////////////////////////////////////////////////////////////////////////////////////

class modAddons extends BackendModule
{
	public $RepoHost = "10.42.0.151:3000";
	
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
		else if($arguments[0] == "install") { return $this->LaunchInstaller($arguments); }
		else if($arguments[0] == "uninstall") { return $this->LaunchUnInstaller($arguments); }
		else if($arguments[0] == "list-installing") { return $this->ListInstalling($arguments); }
		else if($arguments[0] == "installprogress") { return $this->InstallerProgress($arguments); }
		else if($arguments[0] == "downloadicon") { return $this->DownloadIcon($arguments); }
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
	
	function LaunchUpdater()
	{
		
	}
	
	function LaunchInstaller($arguments)
	{
		global $ModulesPath;
		
		if(count($arguments) < 2) { return "FAIL No Module Code was specified."; }
		$modcode = $arguments[1];
		//$jobid = $this->CreateJobID();
		$jobid = $modcode;
		
		$JobDir = $ModulesPath."/addons/data/installjobs";
		if(!file_exists($JobDir))
		{
			$cmd = "mkdir \"".$JobDir."\" -p";
			`$cmd`;
		}
		
		$instcmd = $ModulesPath."/addons/install-addon.sh ".$modcode;
		//exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $instcmd, $outputfile, $pidfile));
		exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $instcmd, $JobDir."/".$jobid, $JobDir."/".$jobid.".pid"));
		
		return ":) ".$jobid;
	}
	
	function LaunchUnInstaller($arguments)
	{
		global $ModulesPath;
		
		if(count($arguments) < 2) { return "FAIL No module code was specified."; }
		$modcode = $arguments[1];
		$jobid = $modcode;
		
		$JobDir = $ModulesPath."/addons/data/uninstalljobs";
		if(!file_exists($JobDir))
		{
			$cmd = "mkdir \"".$JobDir."\" -p";
			`$cmd`;
		}
		
		$instcmd = $ModulesPath."/addons/uninstaller ".$modcode;
		exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $instcmd, $JobDir."/".$jobid, $JobDir."/".$jobid.".pid"));
		
		return ":) ".$jobid;
	}
	
	function ListInstalling($arguments)
	{
		global $ModulesPath;
		$JobDir = $ModulesPath."/addons/data/installjobs";
		
		$ActiveJobs = "";
		$jobs = scandir($JobDir);
		foreach($jobs as $ejob)
		{
			if($ejob != "." && $ejob != "..")
			{
				if(substr($ejob, strlen($ejob) - 4) != ".pid")
				{
					$tcmd = "tail ".$JobDir."/".$ejob." -n 1";
					$res = trim(`$tcmd`);
					
					if($res == "::STATUS:: Installation complete")
					{
						unlink($JobDir."/".$ejob);
						unlink($JobDir."/".$ejob.".pid");
					}
					else
					{
						$ActiveJobs = $ActiveJobs." ".$ejob;
					}
				}
			}
		}
		
		return trim($ActiveJobs);
	}
	
	function InstallerProgress($arguments)
	{
		global $ModulesPath;
		
		if(count($arguments) < 2) { return "FAIL No jobid Specified"; }
		$jobid = $arguments[1];
		
		$JobDir = $ModulesPath."/addons/data/installjobs";
		if(!file_exists($JobDir."/".$jobid)) { return "FAIL Job not found. (".$JobDir."/".$jobid.")"; }
		
		$flines = file($JobDir."/".$jobid);
		$lstat = "";
		foreach($flines as $efl)
		{
			if(strlen($efl) > 11 && substr($efl, 0, 11) == "::STATUS:: ") { $lstat = substr($efl, 11); }
		}
		
		if($lstat == "") { $lstat = "Processing..."; }
		return ":) ".trim($lstat);
	}
	
	function CreateJobID()
	{
		$letters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$jobid = "";
		
		for($i = 0; $i < 12; $i++)
		{
			$jobid = $jobid.substr($letters, rand(0, strlen($letters) - 1), 1);
		}
		
		return $jobid;
	}
	
	function DownloadIcon($arguments)
	{
		if(count($arguments) < 2) { return "FAIL No modcode specified"; }
		
		$instcmd = "wget -O ".PUBLICHTMLPATH."/images/module-icons/".$arguments[1].".png http://".$this->RepoHost."/addons/icon/".$arguments[1];
		
		exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $instcmd, "/tmp/naspi/downicon-".$arguments[1], "/tmp/naspi/downicon-".$argument[1].".pid"));

		return ":) Downloading icon";
	}
}

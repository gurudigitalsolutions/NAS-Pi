<?//	Pi-NAS Module addons modAddOns
///////////////////////////////////////////////////////////////////////////////
//
//	NAS-Pi HelloWorld Module
 
class modAddOns extends PiNASModule
{

	public $MandatoryAddOns = array("admin", "users", "files", "addons");
	public $RepoHost = "10.42.0.151:3000";
	public $RepoPath = "/addons/list";
	public $ReupdatePeriod = 3600;
	
	public function Initialize()
	{
		$this->ModuleCode = "addons";
		$this->MenuTitle = "Add Ons";
		
		$this->Description = "Easily add and remove NAS-Pi Add Ons.";
		$this->Author = "Brian Murphy";
		$this->Version = "v13.07.09";
		$this->AuthorURL = "http://www.gurudigitalsolutions.com";
 
		$this->AddSubMenu("home", "Installed");
		$this->AddSubMenu("browse", "Browse");
	}
 
	public function Render()
	{
		global $RequestVars;
		global $CurrentSessionData;
		global $StyleSheets;
		global $Scripts;
		global $Modules;
		
		
		$StyleSheets[] = "addons";
		$Scripts[] = "addons";
		
		$toret = "";
 
		if($RequestVars["sub"] == "") { $RequestVars["sub"] = "home"; }
 
		if($RequestVars["sub"] == "home")
		{
			$template = file_get_contents(MODULEPATH."/addons/templates/main.html");
			$EachAddOnTmp = file_get_contents(MODULEPATH."/addons/templates/addon-each.html");
			$AddOnIconTmp = file_get_contents(MODULEPATH."/addons/templates/addon-icon.html");
			$AuthorLinkTmp = file_get_contents(MODULEPATH."/addons/templates/addon-authorlink.html");
			$AddOnOptionsTmp = file_get_contents(MODULEPATH."/addons/templates/addon-each-installedoptions.html");
			$AddOnUninstallTmp = file_get_contents(MODULEPATH."/addons/templates/addon-each-uninstalllink.html");
			
			$fulladdon = "";
			$addonlist = DaemonModuleCommand("addons", "list");
			
			$addonprts = explode(" ", $addonlist);
			foreach($addonprts as $eao)
			{
				$tMan = $this->ParseManifest($eao);
				$taot = $EachAddOnTmp;
				$taot = str_replace("[ADDONCODE]", $eao, $taot);
				$taot = str_replace("[TITLE]", $tMan["title"], $taot);
				$taot = str_replace("[VERSION]", $tMan["version"], $taot);
				$taot = str_replace("[DESCRIPTION]", $tMan["description"], $taot);
				
				if(file_exists(PUBLICHTMLPATH."/images/module-icons/".$eao.".png"))
				{
					$taot = str_replace("[MODULEICON]", str_replace("[ADDONCODE]", $eao, $AddOnIconTmp), $taot);
					
				}
				else
				{
					$taot = str_replace("[MODULEICON]", $eao, $taot);
				}
				
				if($tMan["username"] == "") { $taot = str_replace("[AUTHOR]", $tMan["author"], $taot); }
				else
				{
					$tauth = $AuthorLinkTmp;
					$tauth = str_replace("[AUTHOR]", $tMan["author"], $tauth);
					$tauth = str_replace("[AUTHORURL]", "http://".$this->RepoHost."/users/".$tMan["username"], $tauth);
					$taot = str_replace("[AUTHOR]", $tauth, $taot);
				}
				
				$uninsttm = "";
				if($this->IsAddonMandatory($eao))
				{
					$uninsttm = "[Can't Uninstall]";
				}
				else
				{
					$uninsttm = $AddOnUninstallTmp;
					$uninsttm = str_replace("[MODULECODE]", $eao, $uninsttm);
					$uninsttm = str_replace("[TITLE]", $tMan["title"], $uninsttm);
				}
				
				$optstm = $AddOnOptionsTmp;
				$optstm = str_replace("[MODULECODE]", $eao, $optstm);
				$optstm = str_replace("[TITLE]", $tMan["title"], $optstm);
				$optstm = str_replace("[MODULEURL]", "http://".$this->RepoHost."/addons/".$tMan["repoid"], $optstm);
				$optstm = str_replace("[UNINSTALLLINK]", $uninsttm, $optstm);
				
				$taot = str_replace("[ADDONOPTIONS]", $optstm, $taot);
				
				$fulladdon = $fulladdon.$taot;
			}
			
			
			$template = str_replace("[EACHADDON]", $fulladdon, $template);
			$toret = $template;
		}
		else if($RequestVars["sub"] == "browse")
		{
			$toret = $this->BuildRemoteAddons();
		}
		else if($RequestVars["sub"] == "list")
		{
			$toret = $this->BuildFullRepo();
		}
		else if($RequestVars["sub"] == "explore")
		{
			$toret = $this->ExploreAddOns();
		}
		else if($RequestVars["sub"] == "availableaddons")
		{
			echo json_encode($this->AvailablePackages());
			exit;
		}
		else if($RequestVars["sub"] == "installedaddons")
		{
			echo json_encode($this->InstalledPackages());
			exit;
		}
		else if($RequestVars["sub"] == "gettemplate")
		{
			$this->GetBrowserFormatting();
		}
		else
		{
			$toret = "No idea what you are trying to do :(";
		}
 
 
		return $toret;
	}
 
	public function ParseManifest($modcode)
	{
		$manifest = array();
		$mf = file(MODULECONFIGPATH."/".$modcode."/manifest.ini");
		
		foreach($mf as $eline)
		{
			$eline = trim($eline);
			if(substr($eline, 0, 1) != "#")
			{
				$lparts = explode("=", $eline, 2);
				if(count($lparts) > 1)
				{
					$lparts[0] = trim(strtolower($lparts[0]));
					$lparts[1] = trim($lparts[1]);
					
					if($lparts[0] == "modtitle") { $manifest["title"] = $lparts[1]; }
					else if($lparts[0] == "title") { $manifest["title"] = $lparts[1]; }
					else if($lparts[0] == "modcode") { $manifest["code"] = $lparts[1]; }
					else if($lparts[0] == "author") { $manifest["author"] = $lparts[1]; }
					else if($lparts[0] == "repoid") { $manifest["repoid"] = $lparts[1]; }
					else if($lparts[0] == "version") { $manifest["version"] = $lparts[1]; }
					else if($lparts[0] == "authorrepousername") { $manifest["username"] = $lparts[1]; }
					else if($lparts[0] == "description") { $manifest["description"] = $lparts[1]; }
					else if($lparts[0] == "dependencies")
					{
						if(strpos($lparts[1], " ") !== false)
						{
							$dparts = explode(" ", $lparts[1]);
							$manifest["dependencies"] = $dparts;
						}
						else
						{
							$manifest["dependencies"] = array($lparts[1]);
						}
					}
				}
			}
		}
		
		return $manifest;
	}
	
	public function IsAddonMandatory($modcode)
	{
		foreach($this->MandatoryAddOns as $ema)
		{
			if(strtolower($modcode) == strtolower($ema)) { return true; }
		}
		
		return false;
	}
	
	public function BuildRemoteAddons()
	{
		$Packages = array();
		$ch = curl_init();
		
		$addonfile = MODULEPATH."/addons/data/availableaddons.srl";
		if(file_exists($addonfile) && time() - filemtime($addonfile) < $this->ReupdatePeriod)
		{
			$Packages = unserialize(file_get_contents($addonfile));
		}
		else
		{
			$ch = curl_init($this->RepoHost.$this->RepoPath);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$rtext = curl_exec($ch);
			
			
			$Packages = json_decode($rtext);
			file_put_contents($addonfile, serialize($Packages));
		}
		
		$template = file_get_contents(MODULEPATH."/addons/templates/main.html");
		$EachAddOnTmp = file_get_contents(MODULEPATH."/addons/templates/addon-each.html");
		$AddOnIconTmp = file_get_contents(MODULEPATH."/addons/templates/addon-icon.html");
		$AuthorLinkTmp = file_get_contents(MODULEPATH."/addons/templates/addon-authorlink.html");
		$AddOnOptionsTmp = file_get_contents(MODULEPATH."/addons/templates/addon-each-notinstalledoptions.html");
		$EachScreenshotTmp = file_get_contents(MODULEPATH."/addons/templates/screenshot-each.html");
		
		$fulladdon = "";
		foreach($Packages as $ek=>$ev)
		{
			$taot = $EachAddOnTmp;
			$taot = str_replace("[ADDONCODE]", $ev->modcode, $taot);
			$taot = str_replace("[TITLE]", $ev->title, $taot);
			$taot = str_replace("[VERSION]", $ev->version, $taot);
			$taot = str_replace("[DESCRIPTION]", $ev->shortdesc, $taot);
			
			if(file_exists(PUBLICHTMLPATH."/images/module-icons/".$ev->modcode.".png"))
			{
				$taot = str_replace("[MODULEICON]", str_replace("[ADDONCODE]", $ev->modcode, $AddOnIconTmp), $taot);
				
			}
			else
			{
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
				curl_setopt($ch, CURLOPT_URL, $this->RepoHost.$ev->iconurl);
				
				$raw = curl_exec($ch);
				
				$fp = fopen(PUBLICHTMLPATH."/images/module-icons/".$ev->modcode.".png", 'x');
				fwrite($fp, $raw);
				fclose($fp);
				
				$taot = str_replace("[MODULEICON]", str_replace("[ADDONCODE]", $ev->modcode, $AddOnIconTmp), $taot);
			}
			
			$taot = str_replace("[AUTHOR]", $ev->displayname, $taot);
			/*if($ev->Author->URL == "") { $taot = str_replace("[AUTHOR]", $ev->author->URL, $taot); }
			else
			{
				$tauth = $AuthorLinkTmp;
				$tauth = str_replace("[AUTHOR]", $ev->Author->AuthorName, $tauth);
				$tauth = str_replace("[AUTHORURL]", $ev->Author->URL, $tauth);
				$taot = str_replace("[AUTHOR]", $tauth, $taot);
			}*/
			
			$optstm = $AddOnOptionsTmp;
			$optstm = str_replace("[MODULECODE]", $ev->modcode, $optstm);
			$optstm = str_replace("[TITLE]", $ev->title, $optstm);
			$optstm = str_replace("[DESCRIPTION]", $ev->description, $optstm);
			
			if(count($ev->screenshots) > 0)
			{
				$allss = "";
				foreach($ev->screenshots as $ess)
				{
					$tss = $EachScreenshotTmp;
					$tss = str_replace("[SCREENSHOTURL]", "http://".$this->RepoHost.$ess, $tss);
					$allss = $allss.$tss;
				}
				$optstm = str_replace("[SCREENSHOTS]", $allss, $optstm);
			}
			else
			{
				$optstm = str_replace("[SCREENSHOTS]", "No screenshots available", $optstm);
			}
			
			$taot = str_replace("[ADDONOPTIONS]", $optstm, $taot);
			
			$fulladdon = $fulladdon.$taot;
		}
		
		$template = str_replace("[EACHADDON]", $fulladdon, $template);
		$toret = $template;
		
		curl_close($ch);
		
		return $toret;
	}
	
	function InstalledPackages()
	{
		$addonlist = DaemonModuleCommand("addons", "list");
		$addonnames = explode(" ", $addonlist);
		
		$Packages = array();
		foreach($addonnames as $eao)
		{
			$Packages[$eao] = $this->ParseManifest($eao);
		}
		
		return $Packages;
	}
	
	function AvailablePackages()
	{
		$Packages = array();
		$ch = curl_init();
		
		$addonfile = MODULEPATH."/addons/data/availableaddons.srl";
		if(file_exists($addonfile) && time() - filemtime($addonfile) < $this->ReupdatePeriod)
		{
			$Packages = unserialize(file_get_contents($addonfile));
		}
		else
		{
			$ch = curl_init($this->RepoHost.$this->RepoPath);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$rtext = curl_exec($ch);
			
			
			$Packages = json_decode($rtext);
			file_put_contents($addonfile, serialize($Packages));
		}
		
		return $Packages;
	}
	
	function BuildFullRepo()
	{
		$toret = "";
		$PackagesAvailable = $this->AvailablePackages();
		$PackagesInstalled = $this->InstalledPackages();
		
		$MainTemp = file_get_contents(MODULEPATH."/addons/templates/main.html");
		$EachAddOnTmp = file_get_contents(MODULEPATH."/addons/templates/addon-each.html");
		$AddOnIconTmp = file_get_contents(MODULEPATH."/addons/templates/addon-icon.html");
		$AuthorLinkTmp = file_get_contents(MODULEPATH."/addons/templates/addon-authorlink.html");
		$AddOnOptionsTmp = file_get_contents(MODULEPATH."/addons/templates/addon-each-notinstalledoptions.html");
		$EachScreenshotTmp = file_get_contents(MODULEPATH."/addons/templates/screenshot-each.html");
		
		$fulladdon = "";
		$usedcodes = array();
		foreach($PackagesAvailable as $ek=>$ev)
		{
			$usedcodes[$ev->modcode] = true;
			$taot = $EachAddOnTmp;
			$taot = str_replace("[ADDONCODE]", $ev->modcode, $taot);
			$taot = str_replace("[TITLE]", $ev->title, $taot);
			$taot = str_replace("[VERSION]", $ev->version, $taot);
			$taot = str_replace("[DESCRIPTION]", $ev->shortdesc, $taot);
			
			$taot = str_replace("[AUTHOR]", $ev->displayname, $taot);
			
			if(file_exists(PUBLICHTMLPATH."/images/module-icons/".$ev->modcode.".png"))
			{
				$taot = str_replace("[MODULEICON]", str_replace("[ADDONCODE]", $ev->modcode, $AddOnIconTmp), $taot);
				
			}
			else
			{
				/*curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
				curl_setopt($ch, CURLOPT_URL, $this->RepoHost.$ev->iconurl);
				
				$raw = curl_exec($ch);
				
				$fp = fopen(PUBLICHTMLPATH."/images/module-icons/".$ev->modcode.".png", 'x');
				fwrite($fp, $raw);
				fclose($fp);*/
				
				DaemonModuleCommand("addons", "downloadicon ".$ev->modcode);
				$taot = str_replace("[MODULEICON]", $ev->modcode, $taot);
				
				//$taot = str_replace("[MODULEICON]", str_replace("[ADDONCODE]", $ev->modcode, $AddOnIconTmp), $taot);
			}
			
			$optstm = $AddOnOptionsTmp;
			$optstm = str_replace("[MODULECODE]", $ev->modcode, $optstm);
			$optstm = str_replace("[TITLE]", $ev->title, $optstm);
			$optstm = str_replace("[DESCRIPTION]", $ev->description, $optstm);
			
			if(count($ev->screenshots) > 0)
			{
				$allss = "";
				foreach($ev->screenshots as $ess)
				{
					$tss = $EachScreenshotTmp;
					$tss = str_replace("[SCREENSHOTURL]", "http://".$this->RepoHost.$ess, $tss);
					$allss = $allss.$tss;
				}
				$optstm = str_replace("[SCREENSHOTS]", $allss, $optstm);
			}
			else
			{
				$optstm = str_replace("[SCREENSHOTS]", "No screenshots available", $optstm);
			}
			
			$taot = str_replace("[ADDONOPTIONS]", $optstm, $taot);
			
			if(array_key_exists($ev->modcode, $PackagesInstalled))
			{
				$taot = str_replace("[INSTALLEDSTYLE]", "addons_row_installed", $taot);
			}
			else
			{
				$taot = str_replace("[INSTALLEDSTYLE]", "addons_row_uninstalled", $taot);
			}
			
			$fulladdon = $fulladdon.$taot;
		}
		
		
		return str_replace("[EACHADDON]", $fulladdon, $MainTemp);
	}
	
	function ExploreAddOns()
	{
		$MainTemp = file_get_contents(MODULEPATH."/addons/templates/main.html");
		
		return $MainTemp;
	}
	
	function GetBrowserFormatting()
	{
		global $RequestVars;
		
		if($RequestVars['template'] == "table")
		{
			echo file_get_contents(MODULEPATH."/addons/templates/browser-table.html");
			exit;
		}
		else if($RequestVars['template'] == "tablerow")
		{
			echo file_get_contents(MODULEPATH."/addons/templates/browser-tablerow.html");
			exit;
		}
		else if($RequestVars['template'] == "zoom")
		{
			echo file_get_contents(MODULEPATH."/addons/templates/browser-zoom.html");
			exit;
		}
		else if($RequestVars["template"] == "screenshot")
		{
			echo file_get_contents(MODULEPATH."/addons/templates/screenshot-each.html");
			exit;
		}
		else if($RequestVars["template"] == "installlink")
		{
			echo file_get_contents(MODULEPATH."/addons/templates/browser-install.html");
			exit;
		}
		else if($RequestVars["template"] == "uninstalllink")
		{
			echo file_get_contents(MODULEPATH."/addons/templates/browser-uninstall.html");
			exit;
		}
		else
		{
			echo "Dunno :(";
			exit;
		}
	}
}
 
?>

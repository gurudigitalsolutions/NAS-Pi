<?//	Pi-NAS Module addons modAddOns
///////////////////////////////////////////////////////////////////////////////
//
//	NAS-Pi HelloWorld Module
 
class modAddOns extends PiNASModule
{

	public $MandatoryAddOns = array("admin", "users", "files");
	
	public function Initialize()
	{
		$this->ModuleCode = "addons";
		$this->MenuTitle = "Add Ons";
		
		$this->Description = "Easily add and remove NAS-Pi Add Ons.";
		$this->Author = "Brian Murphy";
		$this->Version = "v13.06.12";
		$this->AuthorURL = "http://www.gurudigitalsolutions.com";
 
	}
 
	public function Render()
	{
		global $RequestVars;
		global $CurrentSessionData;
		global $StyleSheets;
		global $Scripts;
		global $Modules;
		
		$toret = "";
 
		if($RequestVars["sub"] == "") { $RequestVars["sub"] = "home"; }
 
		if($RequestVars["sub"] == "home")
		{
			$template = file_get_contents(MODULEPATH."/addons/templates/main.html");
			$EachAddOnTmp = file_get_contents(MODULEPATH."/addons/templates/addon-each.html");
			$AddOnIconTmp = file_get_contents(MODULEPATH."/addons/templates/addon-icon.html");
			$AuthorLinkTmp = file_get_contents(MODULEPATH."/addons/templates/addon-authorlink.html");
			
			$fulladdon = "";
			foreach($Modules as $emkey=>$emval)
			{
				$taot = $EachAddOnTmp;
				$taot = str_replace("[ADDONCODE]", $emkey, $taot);
				$taot = str_replace("[TITLE]", $emval->MenuTitle, $taot);
				$taot = str_replace("[VERSION]", $emval->Version, $taot);
				$taot = str_replace("[DESCRIPTION]", $emval->Description, $taot);
				
				if(file_exists(PUBLICHTMLPATH."/images/module-icons/".$emkey.".png"))
				{
					$taot = str_replace("[MODULEICON]", str_replace("[ADDONCODE]", $emkey, $AddOnIconTmp), $taot);
					
				}
				else
				{
					$taot = str_replace("[MODULEICON]", $emkey, $taot);
				}
				
				if($emval->AuthorURL == "") { $taot = str_replace("[AUTHOR]", $emval->Author, $taot); }
				else
				{
					$tauth = $AuthorLinkTmp;
					$tauth = str_replace("[AUTHOR]", $emval->Author, $tauth);
					$tauth = str_replace("[AUTHORURL]", $emval->AuthorURL, $tauth);
					$taot = str_replace("[AUTHOR]", $tauth, $taot);
				}
				$fulladdon = $fulladdon.$taot;
			}
			
			$template = str_replace("[EACHADDON]", $fulladdon, $template);
			$toret = $template;
		}
		else
		{
			$toret = "No idea what you are trying to do :(";
		}
 
 
		return $toret;
	}
 
}
 
?>

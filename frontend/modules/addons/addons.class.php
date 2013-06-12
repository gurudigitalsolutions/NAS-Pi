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
 
	}
 
	public function Render()
	{
		global $RequestVars;
		global $CurrentSessionData;
		global $StyleSheets;
		global $Scripts;
		
		$toret = "";
 
		if($RequestVars["sub"] == "") { $RequestVars["sub"] = "home"; }
 
		if($RequestVars["sub"] == "home")
		{
			$template = file_get_contents(MODULEPATH."/addons/templates/main.html");
			$EachAddOnTmp = file_get_contents(MODULEPATH."/addons/templates/addon-each.html");
			
			$fulladdon = "";
			for($i = 0; $i < 5; $i++)
			{
				$fulladdon = $fulladdon.$EachAddOnTmp;
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

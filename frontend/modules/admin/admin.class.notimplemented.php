<?//	Pi-NAS Module admin modAdmin
///////////////////////////////////////////////////////////////////////////////
//
//	Pi-NAS Admin Module
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class modAdmin extends PiNASModule
{
	

	public function Initialize()
	{
		$this->ModuleCode = "admin";
		$this->MenuTitle = "Admin";
		
		$this->Description = "This Add On is used to administrate NAS-Pi.";
		$this->Author = "Brian Murphy";
		$this->Version = "v00.01.01";
		$this->AuthorURL = "http://www.gurudigitalsolutions.com";
		
		//$this->AddSubMenu("sources", "Sources", true, array("admin", "filesource"));
		//$this->AddSubMenu("browse", "Browse");
		
		//$this->AddSubAuth("moreinfo", array("admin", "filesource"));
		$this->AuthRequired = true;
		$this->AllowGroups = array("admin");
	}
	
	public function Render()
	{
		global $RequestVars;
		global $StyleSheets;
		global $Scripts;
		global $CurrentSessionData;
		global $Modules;
		$toret = "";
		
		foreach($Modules as $emkey=>$emod)
		{
			$toret = $toret.$emkey."<br />";
		}
		
		
		return $toret;
	}
	
	
}

?>

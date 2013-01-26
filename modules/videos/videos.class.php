<?//	Pi-NAS Module videos modVideos
///////////////////////////////////////////////////////////////////////////////
//
//	Pi-NAS Videos Module
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class modVideos extends PiNASModule
{
	
	public function Initialize()
	{
		$this->ModuleCode = "videos";
		$this->MenuTitle = "Videos";
		
		$this->AddSubMenu("config", "Configuration");
		$this->AddSubMenu("remote", "Remote Control");
	}
	
	public function Render()
	{
		global $Modules;
		$toret = "";
		
		foreach($Modules as $emkey=>$emval)
		{
			$toret = $toret.$emkey."=&gt;".$emval->ModuleCode."<br />\n";
		}
		
		return $toret;
	}
}

?>

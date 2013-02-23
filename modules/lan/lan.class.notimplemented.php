<?//	Pi-NAS Module lan modLAN
///////////////////////////////////////////////////////////////////////////////
//
//	Pi-NAS Files Module
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2012, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class modLAN extends PiNASModule
{
	
	public function Initialize()
	{
		$this->ModuleCode = "lan";
		$this->MenuTitle = "LAN";
		
	}
	
	public function Render()
	{
		global $Modules;
		$toret = "";
		
		foreach($Modules as $emkey=>$emval)
		{
			$toret = $toret.$emkey."=&gt;".$emval."<br />\n";
		}
		
		return $toret;
	}
}

?>

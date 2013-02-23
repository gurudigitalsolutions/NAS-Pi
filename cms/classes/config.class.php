<?/////////////////////////////////////////////////////////////////////////////
//
//	NAS-Pi Configuration Class
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class NASPiConfig
{
	public $ModulesEnabled = array();
	
	
	function IsModuleEnabled($module)
	{
		if(!array_key_exists($module, $this->ModulesEnabled)) { return true; }
		
		if($this->ModulesEnabled[$module] == true) { return true; }
		return false;
	}
	
	function DisableModule($module)
	{
		$this->ModulesEnabled[$module] = false;
		$this->Save();
	}
	
	function EnableModule($module)
	{
		$this->ModulesEnabled[$module] = true;
		$this->Save();
	}
	
	function Save()
	{
		file_put_contents(CMSPATH."/data/cms.cfg", serialize($this));
	}
}

?>

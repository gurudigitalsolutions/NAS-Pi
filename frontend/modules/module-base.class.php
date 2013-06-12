<?/////////////////////////////////////////////////////////////////////////////
//
//	Pi-NAS Module Base Class
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2012, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class PiNASModule
{
	public $ModuleCode = "";
	public $MenuDisplay = true;
	public $MenuTitle = "";
	public $SubMenus = array();
	public $AuthRequired = false;
	public $AllowGroups = array();
	public $SubAuthList = array();
	
	public $Author = "Unknown Author";
	public $Version = "v0.0.1";
	public $AuthorURL = "";
	public $Description = "No description.";
	
	function Initialize()
	{
		$this->ModuleName = "default";
		$this->MenuTitle = "Default Mod";
		return true;
	}
	
	function Render()
	{
		return "Nothing to render.";
	}
	
	function AddSubMenu($submenucode, $submenutitle, $authrequired = false, $authgroups = array())
	{
		$this->SubMenus[$submenucode] = array("title"=>$submenutitle, "authrequired"=>$authrequired, "authgroups"=>$authgroups);
		
		if($authrequired == true) { $this->AddSubAuth($submenucode, $authgroups); }
		return true;
	}
	
	function AddSubAuth($subcode, $authgroups)
	{
		//if(!array_key_exists($subcode, $this->SubAuthList))
		//{
			$this->SubAuthList[$subcode] = $authgroups;
		//	return true;
		//}
		return true;
	}
	
}

?>

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
		return true;
	}
}

?>

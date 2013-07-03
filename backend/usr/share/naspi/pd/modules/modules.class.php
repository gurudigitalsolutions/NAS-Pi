<?//	NAS-Pi	Backend-Module	base
/////////////////////////////////////////////////////////////////////////////////////
//
//	This class is the base of what the backend modules for pd need to be formed like.
//
/////////////////////////////////////////////////////////////////////////////////////

class BackendModule
{
	public $ModuleCode = "";
	public $ModuleTitle = "";
	public $ModuleVersion = "";
	public $Author = "";
	public $Description = "";
	
	function Initialize()
	{
		$this->ModuleCode = "backendbase";
		$this->ModuleTitle = "Backend Base Class";
		$this->Version = "0.2013.07.02";
		$this->Author = "Brian Murphy";
		$this->Description = "This is an abstraction of how the backend classes need to be formed.";
	}
	
	function ProcessCommand($arguments = array())
	{
		return "FAIL Nothing to do";
	}
}



?>

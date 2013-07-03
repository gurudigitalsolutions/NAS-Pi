<?//	NAS-Pi Module files modFiles
/////////////////////////////////////////////////////////////////////////////////////
//
//	This class is the back-end support for the files module.  It will handle all of
//	the mounting and stuff.
//
/////////////////////////////////////////////////////////////////////////////////////

class modFiles extends BackendModule
{
	function Initialize()
	{
		$this->Code = "files";
		$this->Version = "0.2013.07.02";
		$this->Author = "Brian Murphy";
		$this->ModuleTitle = "Files";
		$this->Description = "Support for mounting and sharing filesystems.";
	}
	
	function ProcessCommand($arguments)
	{
		//	Are we mounting, unmounting, or what?
		//
		//	TODO - this :)
		
		return ":) Process successfully completed";
	}
}

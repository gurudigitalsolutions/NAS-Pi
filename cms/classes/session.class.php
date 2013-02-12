<?/////////////////////////////////////////////////////////////////////////////
//
//	Session Representation
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class UserSession
{
	public $ID = "";
	public $Username = "";
	public $IP = "";
	public $LastActive = 0;
	public $FileBrowserDirs = array("/", "/");
	
	public function Save()
	{
		file_put_contents(MODULEPATH."/users/sessions/".$this->ID, serialize($this));
		return true;
	}
}

?>

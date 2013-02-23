<?//	Pi-NAS Component modbtguru settings
/////////////////////////////////////////////////////////////////////////////
//
//	Settings for btguru
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class btguruSettings
{
	public $Host = "127.0.0.1";
	public $Port = 9091;
	
	public function Load()
	{
		if(file_exists(MODULEPATH."/btguru/settings.cfg"))
		{
			return unserialize(MODULEPATH."/btguru/settings.cfg");
		}
	}
	
	public function Save()
	{
		file_put_contents(MODULEPATH."/btguru/settings.cfg", serialize($this));
	}
}

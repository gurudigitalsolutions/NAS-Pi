<?//	Pi-NAS Component modFiles FileSource SMB
///////////////////////////////////////////////////////////////////////////////
//
//	Pi-NAS modFiles FileSource SMB Class
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class FileSourceSMB extends FileSource
{
	public $RemoteHost = "";
	public $RemotePath = "/";
	public $Username = "";
	public $Password = "";
	
	
	function CreateFromForm()
	{
		global $RequestVars;
		
		$this->RemoteHost = $RequestVars["hostname"];
		$this->RemotePath = $RequestVars["remotepath"];
		$this->Username = $RequestVars["username"];
		$this->Password = $RequestVars["password"];
		$this->Title = $RequestVars["title"];
		$this->SourceCode = $RequestVars["sourcecode"];
		$this->FSType = "smb";
		if(array_key_exists("enabled", $RequestVars)) { $this->Enabled = true; } else { $this->Enabled = false; }
		
		if($this->RemoteHost == "") { return false; }
		if($this->RemotePath == "") { $this->RemotePath = "/"; }
		if($this->Username == "") { return false; }
		if($this->Password == "") { return false; }
		if($this->Title == "") { return false; }
		if($this->SourceCode == "") { return false; }
		//if($RequestVars["enabled"] == "") { $this->Enabled = false; } else { $this->Enabled = true; }
		
		return true;
	}
	
	function InitFormElements()
	{
		$this->InitBasicFormElements();
		
		$this->AddFormElement("hostname", "text", "Hostname", "RemoteHost");
		$this->AddFormElement("remotepath", "text", "Remote Path", "RemotePath");
		$this->AddFormElement("username", "text", "Username", "Username");
		$this->AddFormElement("password", "password", "Password", "Password");
	}
	
	function ExtraSourceInfo()
	{
		if($this->RemoteHost == "" || $this->Username == "" || $this->Password == "") { return ""; }
		
		$exfile = file_get_contents(MODULEPATH."/files/sources/source-smb-extra");
		$exfile = str_replace("[USERNAME]", $this->Username, $exfile);
		$exfile = str_replace("[PASSWORD]", $this->Password, $exfile);
		$exfile = str_replace("[REMOTEHOST]", $this->RemoteHost, $exfile);
		
		file_put_contents("/tmp/naspi/source-smb-extra", $exfile);
		`chmod u+x /tmp/naspi/source-smb-extra`;
		$res = `./tmp/naspi/source-smb-extra`;
		
		return trim($res);
		
		
		return $expct;
	}
}

?>

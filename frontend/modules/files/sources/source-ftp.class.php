<?//	Pi-NAS Component modFiles FileSource FTP
///////////////////////////////////////////////////////////////////////////////
//
//	Pi-NAS modFiles FileSource FTP Class
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class FileSourceFTP extends FileSource
{
	public $RemoteHost = "";
	public $RemotePath = "/";
	public $Username = "";
	public $Password = "";
	public $Port = 21;
	
	
	function CreateFromForm()
	{
		global $RequestVars;
		
		$this->RemoteHost = $RequestVars["hostname"];
		$this->RemotePath = $RequestVars["remotepath"];
		$this->Username = $RequestVars["username"];
		$this->Password = $RequestVars["password"];
		//$this->Title = $RequestVars["title"];
		$this->SourceCode = $RequestVars["sourcecode"];
		$this->FSType = "ftp";
		if($RequestVars["port"] != "") { $this->Port = $RequestVars["port"]; }
		if(array_key_exists("enabled", $RequestVars)) { $this->Enabled = true; } else { $this->Enabled = false; }
		
		if($this->RemoteHost == "") { return false; }
		if($this->RemotePath == "") { $this->RemotePath = "/"; }
		if($this->Username == "") { return false; }
		//if($this->Password == "") { return false; }
		//if($this->Title == "") { return false; }
		if($this->SourceCode == "") { return false; }
		$this->Title = $this->SourceCode;
		//if($RequestVars["enabled"] == "") { $this->Enabled = false; } else { $this->Enabled = true; }
		
		return true;
	}
	
	function InitFormElements()
	{
		$this->InitBasicFormElements();
		$this->AddFormElement("hostname", "text", "Hostname", "RemoteHost");
		$this->AddFormElement("remotepath", "text", "Remote Path", "RemotePath");
		$this->AddFormElement("port", "text", "Port", "Port");
		$this->AddFormElement("username", "text", "Username", "Username");
		$this->AddFormElement("password", "password", "Password", "Password");
	}
}

?>

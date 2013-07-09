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
		$expct = "spawn smbclient -L //".$this->RemoteHost."/ -U ".$this->Username."\n";
		$expct = $expct."set pass \"".$this->Password."\"\n".
					"expect {\n".
					"	password: { send \"$pass\\r\" ; exp_continue}\n".
					"	eof exit\n".
					"}";
		
		$cmd = "echo ".$expct." | /usr/bin/expect -f";
		$res = trim(`$cmd`);
		
		return $res;
	}
}

?>

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
		
		file_put_contents("/tmp/source-smb-extra", $exfile);
		`chmod u+x /tmp/source-smb-extra`;
		$res = `/tmp/source-smb-extra`;
		`rm /tmp/source-smb-extra`;
		
		$res = trim($res);
		$resparts = explode("\n", $res);
		
		$ExtraTemplate = file_get_contents(MODULEPATH."/files/templates/smb-extra-main.html");
		$EachShareT = file_get_contents(MODULEPATH."/files/templates/smb-extra-eachshare.html");
		$EachServT = file_get_contents(MODULEPATH."/files/templates/smb-extra-eachserver.html");
		
		$step = 0;
		$Shares = array();
		$Servers = array();
		foreach($resparts as $elin)
		{
			$elin = trim($elin);
			if($step == 0)
			{
				if(substr($elin, 0, 10) == "--------- ") { $step = 1; }
				
			}
			else if($step == 1)
			{
				if(substr($elin, 0, 7) == "Domain=") { $step = 2; }
				else
				{
					$sharename = substr($elin, 0, strpos($elin, " "));
					$type = trim(substr($elin, strlen($sharename)));
					$type = substr($type, 0, strpos($type, " "));
					$comment = trim(substr($elin, strlen($sharename)));
					$comment = trim(substr($comment, strlen($type)));
					$Shares[] = array("sharename"=>$sharename,
										"type"=>$type,
										"comment"=>$comment);
				}
			}
			else if($step == 2)
			{
				if(substr($elin, 0, 10) == "--------- ") { $step = 3; }
			}
			else if($step == 3)
			{
				if($elin == "") { $step = 4; }
				else
				{
					$server = trim(substr($elin, 0, strpos($elin, " ")));
					$comment = trim(substr($elin, strlen($server)));
					
					$Servers[] = array("server"=>$server, "comment"=>$comment);
				}
			}
		}
		
		$toret = $ExtraTemplate;
		$fullshares = "";
		foreach($Shares as $esh)
		{
			$tSh = $EachShareT;
			$tSh = str_replace("[SHARENAME]", $esh["sharename"], $tSh);
			$tSh = str_replace("[TYPE]", $esh["type"], $tSh);
			$tSh = str_replace("[COMMENT]", $esh["comment"], $tSh);
			$tSh = str_replace("[SOURCECODE]", $this->SourceCode, $tSh);
			
			$fullshares = $fullshares.$tSh;
			
		}
		
		$toret = str_replace("[EACHSHARE]", $fullshares, $toret);
		
		$fullshares = "";
		foreach($Servers as $esh)
		{
			$tSh = $EachServT;
			$tSh = str_replace("[SERVER]", $esh["server"], $tSh);
			$tSh = str_replace("[COMMENT]", $esh["comment"], $tSh);
			$fullshares = $fullshares.$tSh;
			
		}
		
		$toret = str_replace("[EACHSERVER]", $fullshares, $toret);
		return $toret;

	}
}

?>

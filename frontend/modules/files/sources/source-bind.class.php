<?//	Pi-NAS Component modFiles FileSource BIND
///////////////////////////////////////////////////////////////////////////////
//
//	Pi-NAS modFiles FileSource BIND Class
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class FileSourceBIND extends FileSource
{
	public $SourceNode = "";
	public $DestinationNode = "";

	
	
	function CreateFromForm()
	{
		global $RequestVars;
		
		$this->OriginalSourceCode = $RequestVars["ogsourcecode"];
		$this->OriginalPath = $RequestVars["ogsourcepath"];
		$this->Title = $RequestVars["title"];
		$this->SourceCode = $RequestVars["sourcecode"];
		$this->FSType = "bind";
		if(array_key_exists("enabled", $RequestVars)) { $this->Enabled = true; } else { $this->Enabled = false; }
		
		if($this->OriginalSourceCode == "") { return false; }
		if($this->OriginalPath == "") { return false; }
		if(substr($this->OriginalPath, 0, 1) == "/")
		{
			if(strlen($this->OriginalPath) == 1) { return false; }
			$this->OriginalPath = substr($this->OriginalPath, 1);
		}
		if($this->Title == "") { return false; }
		if($this->SourceCode == "") { return false; }
		//if($RequestVars["enabled"] == "") { $this->Enabled = false; } else { $this->Enabled = true; }
		
		return true;
	}
	
	function InitFormElements()
	{
		$this->InitBasicFormElements();
		$this->AddFormElement("ogsourcecode", "text", "Original Source", "OriginalSourceCode");
		$this->AddFormElement("ogsourcepath", "text", "Original Path", "OriginalPath");
	}
}

?>

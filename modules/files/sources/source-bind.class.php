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
		
		$this->SourceNode = $RequestVars["sourcenode"];
		$this->Title = $RequestVars["title"];
		$this->SourceCode = $RequestVars["sourcecode"];
		$this->FSType = "bind";
		
		if($this->SourceNode == "") { return false; }
		if($this->Title == "") { return false; }
		if($this->SourceCode == "") { return false; }
		if($RequestVars["enabled"] == "") { $this->Enabled = false; } else { $this->Enabled = true; }
		
		return true;
	}
	
	function InitFormElements()
	{
		$this->InitBasicFormElements();
		$this->AddFormElement("sourcenode", "text", "Original Path", "SourceNode");
	}
}

?>

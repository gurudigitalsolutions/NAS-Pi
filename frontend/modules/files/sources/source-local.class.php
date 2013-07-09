<?//	Pi-NAS Component modFiles FileSource Local
///////////////////////////////////////////////////////////////////////////////
//
//	Pi-NAS modFiles FileSource Local Class
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class FileSourceLocal extends FileSource
{
	public $UUID = "";
	public $Label = "";
	public $Device = "";
	public $FSType = "";
	
	public $FindBy = "uuid";
	
	function CreateFromForm()
	{
		global $RequestVars;
		

		//$this->Title = $RequestVars["title"];
		$this->SourceCode = $RequestVars["sourcecode"];
		$this->FSType = "device";
		$this->UUID = $RequestVars["uuid"];
		$this->Label = $RequestVars["label"];
		$this->Device = $RequestVars["device"];
		if(array_key_exists("enabled", $RequestVars)) { $this->Enabled = true; } else { $this->Enabled = false; }
		
		//if($this->Title == "") { return false; }
		if($this->SourceCode == "") { return false; }
		if($this->UUID == ""
		&& $this->Label == ""
		&& $this->Device == "") { return false; }
		$this->Title = $this->SourceCode;
		//if($RequestVars["enabled"] == "") { $this->Enabled = false; } else { $this->Enabled = true; }
		
		$fnd = array();
		if($this->Device != "")
		{
			$fnd = $this->GetBlocks($this->Device.":");
		}
		else if($this->Label != "") { $fnd = $this->GetBlocks("LABEL=\"".$this->Label."\""); }
		else if($this->UUID != "") { $fnd = $this->GetBlocks("UUID=\"".$this->UUID."\""); }
		
		if(count($fnd) > 0)
		{
			if($this->UUID == "") { $this->UUID = $fnd[0]["uuid"]; }
			if($this->Label == "") { $this->Label = $fnd[0]["label"]; }
			if($this->Device == "") { $this->Device = $fnd[0]["device"]; }
		}
		return true;
	}
	
	function InitFormElements()
	{
		$this->InitBasicFormElements();
		
		$this->AddFormElement("uuid", "text", "UUID", "UUID");
		$this->AddFormElement("label", "text", "Label", "Label");
		$this->AddFormElement("device", "text", "Device", "Device");
	}
	
	public function GetBlocks($search)
	{
		$search = str_replace("\"", "\\\"", $search);
		$search = "\"".$search."\"";
		$blkcmd = `/sbin/blkid | grep $search`;

		$parts = explode("\n", trim($blkcmd));
		$toret = array();
		
		foreach($parts as $epart)
		{
			if(substr($epart, 0, 12) != "/dev/mmcblk0")
			{
				//	We aren't going to list the SD card as a device for this
				$tblock = array();
				
				$devpts = explode(" ", $epart);
				$devpts[0] = str_replace(":", "", $devpts[0]);
				
				$tblock["device"] = $devpts[0];
				
				for($ebp = 1; $ebp < count($devpts); $ebp++)
				{
					$kvprts = explode("=", $devpts[$ebp]);
					
					$tblock[strtolower($kvprts[0])] = str_replace("\"", "", $kvprts[1]);
					
				}
				
				$toret[] = $tblock;
			}
		}
		
		
		return $toret;
	}
}

?>

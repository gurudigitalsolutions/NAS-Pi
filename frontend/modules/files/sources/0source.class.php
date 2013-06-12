<?//	Pi-NAS Component modFiles FileSource
///////////////////////////////////////////////////////////////////////////////
//
//	Pi-NAS modFiles FileSource Class
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class FileSource
{
	public $Title = "";
	public $SourceCode = "";
	public $Enabled = true;
	public $HTTPShareEnabled = true;
	public $HTTPShareAuthRequired = false;
	public $HTTPShareAuthGroups = array();
	public $FormElements =array(
							array(
							"fieldname"=>"title",
							"fieldtype"=>"text",
							"fieldtitle"=>"Title",
							"fieldmap"=>"Title"),
							array(
							"fieldname"=>"sourcecode",
							"fieldtype"=>"text",
							"fieldtitle"=>"Code",
							"fieldmap"=>"SourceCode"),
							array(
							"fieldname"=>"enabled",
							"fieldtype"=>"checkbox",
							"fieldtitle"=>"Enabled",
							"fieldmap"=>"Enabled"));
	
	function __construct()
	{
		
	}
	
	function InitBasicFormElements()
	{
		$this->FormElements = array(
							array(
							"fieldname"=>"title",
							"fieldtype"=>"text",
							"fieldtitle"=>"Title",
							"fieldmap"=>"Title"),
							array(
							"fieldname"=>"sourcecode",
							"fieldtype"=>"text",
							"fieldtitle"=>"Code",
							"fieldmap"=>"SourceCode"),
							array(
							"fieldname"=>"enabled",
							"fieldtype"=>"checkbox",
							"fieldtitle"=>"Enabled",
							"fieldmap"=>"Enabled"));
	}
	
	function Initialize()
	{
		return true;
	}
	
	function Save()
	{
		file_put_contents(MODULEPATH."/files/sources/data/".$this->SourceCode, serialize($this));
		
		
		
		//$cntrl = "/home/media/naspi/mount-naspid control ".$this->SourceCode;
		//$cntrl = "/etc/naspi/control update ".$this->SourceCode;
		$cntrl = "/usr/bin/naspid control commit ".$this->SourceCode;
		$cntrlout = `$cntrl`;
		error_log($cntrlout);
		$cntrl = "/usr/bin/naspid control update ".$this->SourceCode;
		$cntrlout = `$cntrl`;
		error_log($cntrlout);
	}
	
	function AddFormElement($fieldname, $fieldtype, $fieldtitle,$fieldmap)
	{
		$this->FormElements[] = array("fieldname"=>$fieldname, "fieldtype"=>$fieldtype, "fieldtitle"=>$fieldtitle, "fieldmap"=>$fieldmap);
	}
	
	function Delete()
	{
		$cntrl = "/usr/bin/naspid control remove ".$this->SourceCode;
		$cntrlout = `$cntrl`;
		error_log($cntrlout);
	}
}

?>

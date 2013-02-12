<?//Pi-NAS Component modFiles BrowserPane
/////////////////////////////////////////////////////////////////////////////
//
//	PHP Script
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2012, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class BrowserPane
{
	public $Dir = "";
	public $SourceCode = "";
	public $PaneID = 0;
	public $Nodes = array();
	public $Sources = array();
	
	public function LoadDir($dir = "")
	{
		if($dir != "") { $this->Dir = $dir; }
		
		if(count($this->Sources) == 0) { $this->LoadSources(); }
		
		if($this->Dir == "/") { $this->LoadSourcesDir(); return; }
		
		if(substr($this->Dir, 0, 1) == "/") { $this->Dir = substr($this->Dir, 1); }
		$this->SourceCode = $this->SourceCodeFromDir($this->Dir);
		
		if(!$this->IsSourceAccessable($this->SourceCode)) { return; }
		
		if(!is_dir("/media/".$this->Dir) && is_file("/media/".$this->Dir))
		{
			$mimetype = $this->MimeTypeOf("/media/".$this->Dir);
			$filename = substr("/media/".$this->Dir, strrpos("/media/".$this->Dir, "/") + 1);
			
			header("Content-Type: ".$mimetype);
			header("Expires: 0");
			header("Content-Disposition: attachment; filename=".$filename);
			header("Content-Length: ".filesize("/media/".$this->Dir));
			readfile("/media/".$this->Dir);
			exit;
		}
		
		$ffls = scandir("/media/".$this->Dir);
		$tnids = 0;
		foreach($ffls as $ekey=>$efile)
		{
			if($efile != "." && $efile != "..")
			{
				$tnode = new FileBrowserNode();
				$tnode->Name = $efile;
				$tnode->ID = $tnids;
				
				if(!is_dir("/media/".$this->Dir."/".$efile)) { $tnode->IsFile = true; }
				
				$mimetype = $this->MimeTypeOf("/media/".$this->Dir."/".$efile);
										
				$mtprts = explode("/", $mimetype);
				$tnode->Icon = $mtprts[0];
				//$tnode->Icon = $mimetype;
				$this->Nodes[] = $tnode;
				
				$tnids++;
			}
		}
	}
	
	private function LoadSourcesDir()
	{
		global $CurrentUser;
		
		$idcnt = 0;
		foreach($this->Sources as $ekey=>$es)
		{
			if($es->Enabled)
			{
				if($es->HTTPShareEnabled)
				{
					if(!$es->HTTPShareAuthRequired
					|| ($es->HTTPShareAuthRequired
						&& $CurrentUser != ""
						&& $CurrentUser->GroupMemberOfAny($es->HTTPShareAuthGroups)))
					{
						$tnode = new FileBrowserNode();
						$tnode->Name = $es->SourceCode;
						$tnode->ID = $idcnt;
						
						$mimetype = $this->MimeTypeOf("/media/".$es->SourceCode);
										
						$mtprts = explode("/", $mimetype);
						$tnode->Icon = $mtprts[0];
						
						$this->Nodes[] = $tnode;
						
						$idcnt++;
					}
				}
			}
		}
	}
	
	private function LoadSources()
	{
		$srclist = scandir(MODULEPATH."/files/sources/data");
		foreach($srclist as $es)
		{
			if($es != "." && $es != "..")
			{
				if(!is_dir(MODULEPATH."/files/sources/data/".$es))
				{
					$this->Sources[$es] = unserialize(file_get_contents(MODULEPATH."/files/sources/data/".$es));
				}
			}
		}
	}
	
	private function SourceCodeFromDir($dir)
	{
		if(substr($dir, 0, 1) == "/") { $dir = substr($dir, 1); }
		
		if(strpos($dir, "/") === false) { return $dir; }
		return substr($dir, 0, strpos($dir, "/"));
	}
	
	public function IsSourceAccessable($sourcecode)
	{
		if(!array_key_exists($sourcecode, $this->Sources)) { return false; }
		
		$es = $this->Sources[$sourcecode];
		if($es->Enabled)
		{
			if($es->HTTPShareEnabled)
			{
				if(!$es->HTTPShareAuthRequired
				|| ($es->HTTPShareAuthRequired
					&& $CurrentUser != ""
					&& $CurrentUser->GroupMemberOfAny($es->HTTPShareAuthGroups)))
				{
					return true;
				}
			}
		}
		
		return false;
	}
	
	public function MimeTypeOf($fullpath)
	{
		$mtcmd = "file -bi \"".$fullpath."\"";
		return trim(`$mtcmd`);
	}
}

?>

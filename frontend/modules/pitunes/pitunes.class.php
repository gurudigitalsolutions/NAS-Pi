<?//	Pi-NAS Module pitunes modPiTunes
///////////////////////////////////////////////////////////////////////////////
//
//	Pi-NAS PiTunes Module
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class modPiTunes extends PiNASModule
{
	

	public function Initialize()
	{
		$this->ModuleCode = "pitunes";
		$this->MenuTitle = "PiTunes";
		
		$this->Description = "Use your Raspberry Pi as a music player.";
		$this->Author = "Brian Murphy";
		$this->Version = "v13.07.10";
		$this->AuthorURL = "http://www.gurudigitalsolutions.com";
		
		//$this->AddSubMenu("sources", "Sources", true, array("admin", "filesource"));
		//$this->AddSubMenu("browse", "Browse");
		
		//$this->AddSubAuth("moreinfo", array("admin", "filesource"));
	
	}
	
	public function Render()
	{
		global $RequestVars;
		global $StyleSheets; $StyleSheets[] = "pitunes";
		global $Scripts; $Scripts[] = "pitunes";
		global $CurrentSessionData;
		global $Modules;
		$toret = "";
		
		$sub = $RequestVars["sub"];
		if($sub == "") { $sub = "home"; }
		
		if($sub == "home") { $toret = $this->BuildPlayer(); }
		else if($sub == "query") { $this->RunQuery($RequestVars["args"]); }
		else if($sub == "template") { $this->GetTemplate(); }
		else { $toret = "PiTunes?"; }
		
		return $toret;
	}
	
	public function RunQuery($args)
	{
		if($args == "") { echo "Can't query nothing..."; exit; }
		
		if($args == "next") { $this->CnD("next"); }
		else if($args == "prev") { $this->CnD("prev"); }
		else if($args == "pause") { $this->CnD("pause"); }
		else if($args == "stop") { $this->CnD("stop"); }
		else if($args == "shuffle?") { $this->CnD(":: shuffle?"); }
		else if($args == "shuffle on") { $this->CnD("shuffle on"); }
		else if($args == "shuffle off") { $this->CnD("shuffle off"); }
		else if($args == "shuffle toggle") { $this->CnD("shuffle toggle"); }
		else if($args == "elapsed") { $this->CnD(":: elapsed"); }
		else if($args == "remaining") { $this->CnD(":: remaining"); }
		else if($args == "setvolume") { $this->CnD("v ".$RequestVars["level"]); }
		else if($args == "currentlyplaying") { $this->CnD(":: currentlyplaying"); }
		else if($args == "queue")
		{ 
			$type = $RequestVars["type"];
			$path = $RequestVars["path"];
			$this->CnD("queue ".$type." ".$path);
		}
		else if($args == "getplaylist") { $this->GetPlaylist(); }
		else if(substr($args, 0, 19) == "getplaylistentries ") { $this->CnD(":: ".$args); }
		else { echo "Can't do anything yet..."; exit; }
		
		echo "You shoujldn't have made it here."; exit;
	}
	
	public function CnD($cmd)
	{
		//	CommandAndDie..  Send a command and just exit the script
		echo DaemonModuleCommand("pitunes", $cmd);
		exit;
		
	}
	
	public function BuildPlayer()
	{
		$PageTemplate = file_get_contents(MODULEPATH."/pitunes/templates/main.html");
		$toret = $PageTemplate;
		
		
		
		return $toret;
	}
	
	public function GetPlaylist()
	{
		$fn = MODULEPATH."/pitunes/data/playlistcache.srl";
		if(file_exists($fn))
		{
			$Playlist = unserialize(file_get_contents($fn));
			echo json_encode($Playlist);
			exit;
		}
		
		$nothing = array();
		echo json_encode($nothing);
		exit;
	}
	
	public function GetTemplate()
	{
		global $RequestVars;
		$tmp = $RequestVars['template'];
		if($tmp == "") { echo "FAIL No template suggested."; exit; }
		else if($tmp == "playlist") { echo file_get_contents(MODULEPATH."/pitunes/templates/playlist.html"); exit; }
		else if($tmp == "playlistrow") { echo file_get_contents(MODULEPATH."/pitunes/templates/playlist-eachrow.html"); exit; }
		
		echo "I dunno?";
		exit;
	}
	
}

?>

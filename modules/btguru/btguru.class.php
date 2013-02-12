<?//	Pi-NAS Module btguru modbtguru
/////////////////////////////////////////////////////////////////////////////
//
//	btguru module for NAS-Pi
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class modbtguru extends PiNASModule
{
	public $TransmissionHost = "10.42.0.100";
	public $TransmissionPort = "9091";
	
	public function Initialize()
	{
		$this->ModuleCode = "btguru";
		$this->MenuTitle = "BitTorrent";
		
		//$this->AddSubMenu("sources", "Sources", true, array("admin", "filesource"));
		//$this->AddSubMenu("browse", "Browse");
	}
	
	public function Render()
	{
		global $StyleSheets; $StyleSheets[] = "btguru";
		global $RequestVars;
		global $Scripts; $Scripts[] = "btguru";
		global $CurrentSessionData;
		
		$sub = $RequestVars['sub'];
		
		if($sub == null || $sub == "")
		{
			$template = file_get_contents(MODULEPATH."/btguru/templates/layout.html");
			$toret = $template;
		}
		else if($sub == "transmissionstate") { $this->trigTransmissionState(); }
		else if($sub == "search") { $this->trigSearch(); }
		else if($sub == "addtorrent") { $this->trigAddTorrent(); }
		else if($sub == "torrentprogress") { $this->trigTorrentProgress(); }
<<<<<<< HEAD
		else if($sub == "template") { $this->trigTemplate(); }
=======
>>>>>>> bd85ace10cbfd53db7ff2f999409f7b0dad1e94b
		
		
		return $toret;
	}
	
	public function trigTransmissionState()
	{
<<<<<<< HEAD
		global $RequestVars;
		$js = $RequestVars['js'];
		
		if($js != 1) { $js = false; } else { $js = true; }
		
		$rpc = new TransmissionRPC("http://".$this->TransmissionHost.":".$this->TransmissionPort."/transmission/rpc");
		$result = $rpc->get(array(), array( "id", "name", "status", "doneDate", "haveValid", "totalSize","rateDownload", "rateUpload", "isFinished", "isStalled", "eta" ));


		if(!$js) { $etortem = file_get_contents(MODULEPATH."/btguru/templates/currenttorrent.html"); }
		else { $etortem = file_get_contents(MODULEPATH."/btguru/templates/currenttorrent-json.html"); }
=======
		$rpc = new TransmissionRPC("http://".$this->TransmissionHost.":".$this->TransmissionPort."/transmission/rpc");
		$result = $rpc->get(array(), array( "id", "name", "status", "doneDate", "haveValid", "totalSize","rateDownload", "rateUpload", "isFinished", "isStalled", "eta" ));

		$etortem = file_get_contents(MODULEPATH."/btguru/templates/currenttorrent.html");
>>>>>>> bd85ace10cbfd53db7ff2f999409f7b0dad1e94b

		if($result->result == "success")
		{
			$toret = "";
			foreach($result->arguments->torrents as $etorrent)
			{
				$tout = $etortem;
				
				$paused = true;
				
				$prctfin = 0;
				if(property_exists($etorrent, "isFinished"))
				{
					$tout = str_replace("[STATS]", round(($etorrent->haveValid / 1000000), 2)."M", $tout);
					$tout = str_replace("[DOWNLOADRATE]", "", $tout);
					$tout = str_replace("[UPLOADRATE]", "", $tout);
					$prctfin = 100;
					$paused = false;
				}
				else
				{
					if($etorrent->eta >= 0) { $paused = false; }
					
					$stnumbers = round(($etorrent->haveValid / 1000000), 2)."M / ".round(($etorrent->totalSize / 1000000), 2)."M";
					$stdown = "0 kb/s down";
					$stup = "0 kb/s up";
					
					if(property_exists($etorrent, "rateDownload"))
					{
						$stdown = (round($etorrent->rateDownload / 1024, 2))." kB/s down";
					}
					
					if(property_exists($etorrent, "rateUpload"))
					{
						$stup = (round($etorrent->rateUpload / 1024, 2))." kB/s up";
					}
					
					if($paused) { $stdown = ""; $stup = ""; }
					
					$tout = str_replace("[STATS]", $stnumbers, $tout);
					$tout = str_replace("[DOWNLOADRATE]", $stdown, $tout);
					$tout = str_replace("[UPLOADRATE]", $stup, $tout);
					$prctfin = ceil(($etorrent->haveValid / $etorrent->totalSize) * 100);
					
					
				}
				
				$progextra = "";
				if($paused) { $progextra = "&x=1"; }
				
				$tout = str_replace("[NAME]", $etorrent->name, $tout);
				$tout = str_replace("[PROGRESSPCT]", $prctfin.$progextra, $tout);
				
				$toret = $toret.$tout;
				
			}
			
<<<<<<< HEAD
			if($js)
			{
				$toret = trim($toret);
				$toret = substr($toret, 0, strlen($toret) -1);
				$toret = "{[".$toret."]}";
			}
=======
>>>>>>> bd85ace10cbfd53db7ff2f999409f7b0dad1e94b
			echo $toret;
		}
		else
		{
			echo "Couldn't load state.";
		}
		
		exit;
	}

	public function trigSearch()
	{
		$srch = new btguruSearch();
		$srch->Initialize();
		$srch->Run();
		
		exit;
	}
	
	public function trigAddTorrent()
	{
		global $RequestVars;
		$torrent = $RequestVars['torrentlink'];
<<<<<<< HEAD
		
		
=======
>>>>>>> bd85ace10cbfd53db7ff2f999409f7b0dad1e94b

		$rpc = new TransmissionRPC("http://".$this->TransmissionHost.":".$this->TransmissionPort."/transmission/rpc");
		$result = $rpc->add($torrent);

<<<<<<< HEAD
		if($result->result == "invalid or corrupt torrent file") { echo "FAIL Corrupt or invalid file.\n(".$torrent.")"; }
		else if($result->result == "success") { echo "YEAH Success!"; }
		else { echo "FAIL Weird: ".$result->result; }
=======
		if($result->result == "invalid or corrupt torrent file") { echo ":("; }
		else if($result->result == "success") { echo ":)"; }
		else { echo ":("; }
>>>>>>> bd85ace10cbfd53db7ff2f999409f7b0dad1e94b
		
		exit;
	}
	
	public function trigTorrentProgress()
	{
		global $RequestVars;
		$width = 245;
		$height = 10;
		$finished = 0;
		$paused = false;
		if(isset($RequestVars['p'])) { $finished = $_GET['p']; }
		if(isset($RequestVars['w'])) { $width = $_GET['w']; }
		if(isset($RequestVars['h'])) { $height = $_GET['h']; }
		if(isset($RequestVars['x'])) { $paused = true; }

		// filename: progressbar.php  
		// author  : lasantha samarakoon  
		  
		// set the type of data (Content-Type) to PNG image  
		header("Content-Type: image/png");  

		  
		// this method prepare blank true color image with given width and height  
		$im = imagecreatetruecolor($width, $height);  
		  
		// set background color (light-blue)  
		$c_bg = imagecolorallocate($im, 222, 236, 247);  

		// set foreground color (dark-blue)  
		if($paused) { $c_fg = imagecolorallocate($im, 160, 160, 160); }
		else if($finished == 100) { $c_fg = imagecolorallocate($im, 13, 60, 89); }
		else { $c_fg = imagecolorallocate($im, 27, 120, 179); }
		  
		// calculate the width of bar indicator  
		$val_w = round(($finished * ($width - 3)) / 100);  
		  
		// create a rectangle for background and append to the image  
		imagefilledrectangle($im, 0, 0, $width, $height, $c_bg);  
		// create a rectangle for the indicator and appent to the image  
		imagefilledrectangle($im, 2, 2, $val_w, $height - 3, $c_fg);  
		  
		// render the image as a PNG  
		imagepng($im);  
		  
		// finally destroy image resources  
		imagedestroy($im);  
		
		exit;
	}
<<<<<<< HEAD
	
	public function trigTemplate()
	{
		global $RequestVars;
		$tmp = $RequestVars["template"];
		
		if($tmp == "" || $tmp == null) { echo ""; exit; }
		
		if($tmp == "trans-current") { echo file_get_contents(MODULEPATH."/btguru/templates/currenttorrent.html"); exit; }
		if($tmp == "tabs-each") { echo file_get_contents(MODULEPATH."/btguru/templates/tabs-each.html"); exit; }
		if($tmp == "search-outline") { echo file_get_contents(MODULEPATH."/btguru/templates/search/result-outline.html"); exit; }
		if($tmp == "search-each") { echo file_get_contents(MODULEPATH."/btguru/templates/search/result-each.html"); exit; }
		if($tmp == "result-action-add") { echo file_get_contents(MODULEPATH."/btguru/templates/search/result-action-add.html"); exit; }
		if($tmp == "result-action-none") { echo file_get_contents(MODULEPATH."/btguru/templates/search/result-action-none.html"); exit; }
		
		echo "";
		exit;
	}
=======
>>>>>>> bd85ace10cbfd53db7ff2f999409f7b0dad1e94b
}

?>

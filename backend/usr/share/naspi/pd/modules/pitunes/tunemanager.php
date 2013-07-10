#!/usr/bin/php
<?///////////////////////////////////////////////////////////////////////////////////
//
//	PiTunes Manager.  This class is specifically not formatted to auto load by the
//	daemon.  I'm playing a trick on it :)
//
//	Copyright 2013 Guru Digital Solutions
//	Written by Brian Murphy
//
/////////////////////////////////////////////////////////////////////////////////////

$PiTunePlayer = new PiTuneManager();
$PiTunePlayer->Initialize();
$PiTunePlayer->Run();


class PiTuneManager
{
	public $Playlist = array();
	public $PlaylistPos = 0;
	public $Shuffle = false;
	public $SecondsElapsed = 0;
	public $SecondsRemaining = 0;
	public $PlayState = 2;
	public $pipes = array();
	public $Ph = 0;
	
	function Initialize()
	{
		
	}
	
	function Run()
	{
		$descriptorspec = array(
			0 => array("pipe", "r"), // stdin is a pipe that the child will read from
			1 => array("pipe", "w"), // stdout is a pipe that the child will write to
			2 => array("file", "/tmp/errorout.txt", "a") // stderr is a file to write to
		);

		$this->Ph = proc_open("mpg321 -R abcd 2>&1", $descriptorspec, $this->pipes);
		
		//	$pipes now looks like this:
		//	0 => writeable handle connected to child stdin
		//	1 => readable handle connected to child stdout
		//	Any error output will be in /tmp/errorout.txt

		$stdin = fopen("php://stdin", 'r');
		//$stdout = fopen("php://stdout", 'w');
		
		stream_set_blocking($stdin, 0);
		stream_set_blocking($this->pipes[1], 0);

		$starttime = time();
		//fwrite($this->pipes[0], "load Andy_C_Ram_Warehouse.mp3\n");
		$inbuff = "";
		$stdinbuff = "";
		
		if(file_exists("/usr/share/naspi/modules/pitunes/data/playlistcache.srl"))
		{
			$this->Playlist = unserialize(file_get_contents("/usr/share/naspi/modules/pitunes/data/playlistcache.srl"));
		}
		
		$handlestdin = false;
		while($starttime > 180)
		{
			
			//$instr = fgets($pipes[1]);
			$instr = stream_get_line($this->pipes[1], 512, "\n");
			
			$stdintxt = fgets($stdin, 128);
			if(strlen($stdintxt) > 0) { $stdinbuff = trim($stdintxt); $handlestdin = true; }
			
			//if($stdintxt == "\n") { $handlestdin = true; }
			//else { $stdinbuff = $stdinbuff.$stdintxt; }
			
			if(substr($instr, 0, 3) == "@F ")
			{
				$frmprts = explode(" ", $instr);
				$this->SecondsElapsed = $frmprts[3];
				$this->SecondsRemaining = $frmprts[4];
			}
			else if(substr($instr, 0, 3) == "@P ")
			{
				
				$this->PlayState = substr($instr, 3);
				
				if($this->PlayState == 3)
				{
					$this->PlaylistPos = $this->NextTrackNumber();
					if(count($this->Playlist) > $this->PlaylistPos)
					{
						fwrite($this->pipes[0], "load ".$this->Playlist[$this->PlaylistPos]."\n");
					}
				}
			}
			//echo $instr."\n";
			
			//echo "\r\t".$SecondsElapsed." / ".$SecondsRemaining." / ".(time() - $starttime);
			
			if($handlestdin)
			{
				if($stdinbuff == "elapsed") { echo $this->SecondsElapsed."\n"; }
				else if($stdinbuff == "remaining") { echo $this->SecondsRemaining."\n"; }
				else if(substr($stdinbuff, 0, 2) == "v ")
				{
					$vol = substr($stdinbuff, 2);
					if(is_numeric($vol)) { fwrite($this->pipes[0], "g ".$vol."\n"); }
				}
				else if($stdinbuff == "quit" || $stdinbuff == "exit") { $starttime = 0; }
				else if($stdinbuff == "stop") { fwrite($this->pipes[0], "stop\n"); }
				else if($stdinbuff == "pause") { fwrite($this->pipes[0], "pause\n"); }
				else if($stdinbuff == "shuffle on") { $this->Shuffle = true; }
				else if($stdinbuff == "shuffle off") { $this->Shuffle = false; }
				else if($stdinbuff == "shuffle?")
				{
					if($this->Shuffle) { echo "on\n"; } else { echo "off\n"; }
				}
				else if($stdinbuff == "shuffle toggle")
				{
					if($this->Shuffle) { $this->Shuffle = false; } else { $this->Shuffle = true; }
				}
				else if($stdinbuff == "next")
				{
					fwrite($this->pipes[0], "stop\n");
					$this->PlaylistPos = $this->NextTrackNumber();
					if(count($this->Playlist) > $this->PlaylistPos)
					{
						fwrite($this->pipes[0], "load ".$this->Playlist[$this->PlaylistPos]."\n");
					}
				}
				else if($stdinbuff == "prev")
				{
					fwrite($this->pipes[0], "stop\n");
					$this->PlaylistPos--;
					if($this->PlaylistPos < 0) { $this->PlaylistPos = 0; }
					else
					{
						fwrite($this->pipes[0], "load ".$this->Playlist[$this->PlaylistPos]."\n");
					}
				}
				else if(substr($stdinbuff, 0,  12) == "queue track ")
				{
					$track = substr($stdinbuff, 12);
					$this->Playlist[] = $track;
					$this->CachePlaylist();
				}
				else if(substr($stdinbuff, 0, 10) == "queue dir ")
				{
					$dir = substr($stdinbuff, 10);
					$this->ScanToPlaylist($dir);
					$this->CachePlaylist();
				}
				else if($stdinbuff == "clear playlist") { $this->Playlist = array(); $this->PlaylistPos = 0; }
				else if(substr($stdinbuff, 0, 11) == "play track ")
				{
					$track = trim(substr($stdinbuff, 11));
					fwrite($this->pipes[0], "load ".$track."\n");
				}
				else if(substr($stdinbuff, 0, 18) == "playlist position ")
				{
					$pos = trim(substr($stdinbuff, 18));
					if(is_numeric($pos)) { $this->PlaylistPos = $pos; }
				}
				else if($stdinbuff == "currentlyplaying") { echo $this->PlaylistPos."\t".$this->Playlist[$this->PlaylistPos]."\n"; }
				else if($stdinbuff == "getplaylist")
				{
					for($et = 0; $et < count($this->Playlist); $et++)
					{
						echo $et."\t".$this->Playlist[$et]."\n";
					}
					
					echo "*\tEND\n";
				}
				else if(substr($stdinbuff, 0, 19) == "getplaylistentries ")
				{
					$tmp = trim(substr($stdinbuff, 19));
					$eprt = explode(" ", $tmp);
					
					$low = $eprt[0];
					$high = $eprt[1];
					
					if(!is_numeric($low) || !is_numeric($high)) { echo "FAIL Improper inputs\n"; }
					else
					{
						if($low < 0) { $low = 0; }
						if($high > count($this->Playlist) - 1) { $high = count($this->Playlist) - 1; }
						
						if($low > $high) { $t = $low; $low = $high; $high = $t; }
						
						$allpi = "";
						for($ep = $low; $ep <= $high; $ep++)
						{
							$tpi = $ep."\t".$this->Playlist[$ep]."\t&^%$|";
							$allpi = $allpi.$tpi;
						}
						
						echo $allpi."\n";
					}
				}
				else if($stdinbuff == "playlistcount") { echo count($this->Playlist)."\n"; }
				
				$stdinbuff = "";
				$handlestdin = false;
			}
			usleep(10000);
			//echo "end of the loop\n";
			//fwrite($pipes[0], "p\n");
		}


		fclose($this->pipes[0]);
		fclose($this->pipes[1]);
		$return_value = proc_close($this->Ph);
	}
	
	function ScanToPlaylist($dir)
	{
		
		$files = scandir($dir);
		foreach($files as $efile)
		{
			if($efile != "." && $efile != "..")
			{
				if(is_dir($dir."/".$efile))
				{
					$this->ScanToPlaylist($dir."/".$efile);
				}
				else
				{
					$cmd = "file -bi \"".$dir."/".$efile."\"";
					$mime = `$cmd`;
					if(substr($mime, 0, 5) == "audio") { $this->Playlist[] = $dir."/".$efile; }
				}
			}
		}
	}
	
	function NextTrackNumber()
	{
		if(!$this->Shuffle) { return $this->PlaylistPos + 1; }
		
		return rand(0, count($this->Playlist) - 1);
	}
	
	function CachePlaylist()
	{
		$loc = "/usr/share/naspi/modules/pitunes/data";
		if(!file_exists($loc)) { mkdir($loc); }
		
		$loc = $loc."/playlistcache.srl";
		file_put_contents($loc, serialize($this->Playlist));
	}
}



?>

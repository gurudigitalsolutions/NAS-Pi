<?//	Pi-NAS Component modbtguru search
/////////////////////////////////////////////////////////////////////////////
//
//	This script provides the ability to search multiple torrent index sites.
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class btguruSearch
{
	public $NameDumpFile = "";
	public $TransmissionHost = "10.42.0.100";
	public $TransmissionPort = "9091";
	public $SearchResultTemplate = "";
	public $SearchResultOutline = "";
	public $ActionAddTemplate = "";
	public $ActionNoneTemplate = "";
	public $SerializeSearchResult = false;
	public $SearchQuery = "";
	public $MaxSearchResults = 50;
	public $OutputFormat = "";
	public $scrapers = array();
	public $tsm = "";

	public function Initialize()
	{
		global $RequestVars;
		$this->NameDumpFile = MODULEPATH."/btguru/dumps/namedump.txt";
		$this->SearchResultTemplate = file_get_contents(MODULEPATH."/btguru/templates/searchresult.html");
		$this->SearchResultOutline = file_get_contents(MODULEPATH."/btguru/templates/search/result-outline.html");
		$this->ActionAddTemplate = file_get_contents(MODULEPATH."/btguru/templates/search/result-action-add.html");
		$this->ActionNoneTemplate = file_get_contents(MODULEPATH."/btguru/templates/search/result-action-none.html");

		$this->SearchQuery = $RequestVars['query'];

		$this->scrapers = array(
			"tpb"=>new tpb(),
			"kat"=>new kat()
		);
		//$scraper = new tpb();
		//$scraper = new kat();
	}
	
	public function Run()
	{

		$this->scrapers["tpb"]->MaxSearchResults = $this->MaxSearchResults;
		$this->scrapers["tpb"]->SearchTerms = $this->SearchQuery;
		$this->scrapers["tpb"]->Run();
		$this->scrapers["kat"]->MaxSearchResults = $this->MaxSearchResults;
		$this->scrapers["kat"]->SearchTerms = $this->SearchQuery;
		$this->scrapers["kat"]->Run();

		$rpc = new TransmissionRPC("http://".$this->TransmissionHost.":".$this->TransmissionPort."/transmission/rpc");
		$this->tsm = $rpc->get(array(), array( "id", "name", "magnetLink", "eta", "isFinished"));


		$fullr = "";
		$resultid = 0;
		$ttlresults = 0;

		$scraperkeys = array_keys($this->scrapers);
		$scraperindex = array();

		$hashes = array();

		foreach($scraperkeys as $ekey)
		{ 
			$scraperindex[$ekey] = 0; 
			$ttlresults+=count($this->scrapers[$ekey]->ScrapeResults); 
		}

		//$dfh = fopen($this->NameDumpFile, "a");

		for($eitem = 0; $eitem < $ttlresults; $eitem++)
		{
			$turnhighest = 0;
			$turnhighestkey = "";
			
			foreach($scraperkeys as $ekey)
			{
				if(count($this->scrapers[$ekey]->ScrapeResults) > $scraperindex[$ekey])
				{
					if($this->scrapers[$ekey]->ScrapeResults[$scraperindex[$ekey]]["seed"] > $turnhighest)
					{
						$turnhighest = $this->scrapers[$ekey]->ScrapeResults[$scraperindex[$ekey]]["seed"];
						$turnhighestkey = $ekey;
					}
					
					
					
				}
			}
			
			$trtmp = $this->SearchResultTemplate;
			$eres = $this->scrapers[$turnhighestkey]->ScrapeResults[$scraperindex[$turnhighestkey]];

			$torrenthash = $this->ParseHashFromMagnet($eres["link"]);
			if(array_key_exists($torrenthash, $hashes))
			{
				$hashes[$torrenthash]++;
			}
			else
			{
				$hashes[$torrenthash] = 1;
				
				//fwrite($dfh, $torrenthash." ".$eres["name"]."\n");

				$torpres = $this->IsTorrentPresent($eres["link"]);
				
				if($torpres)
				{
					$trtmp = str_replace("[RESULTACTION]", $this->ActionNoneTemplate, $trtmp);
					$trtmp = str_replace("[RESULTCLASS]", "have", $trtmp);
				}
				else
				{
					$trtmp = str_replace("[RESULTACTION]", $this->ActionAddTemplate, $trtmp);
					
					$trtmp = str_replace("[RESULTCLASS]", "new", $trtmp);
				}
				
				$trtmp = str_replace("[NAME]", $eres["name"], $trtmp);
				$trtmp = str_replace("[DATE]", $eres["date"], $trtmp);
				$trtmp = str_replace("[SEEDCOUNT]", $eres["seed"], $trtmp);
				$trtmp = str_replace("[LEECHCOUNT]", $eres["leech"], $trtmp);
				$trtmp = str_replace("[SIZE]", $eres["size"], $trtmp);
				$trtmp = str_replace("[TORRENTURL]", $eres["link"], $trtmp);
				$trtmp = str_replace("[RESULTID]", $eitem, $trtmp);
				$trtmp = str_replace("[SOURCECOUNT]", "[COUNT-".$torrenthash."]", $trtmp);
				
				$fullr = $fullr.$trtmp;
			}
			$resultid++;
			
			$scraperindex[$turnhighestkey]++;
		}

		//fclose($dfh);

		foreach($hashes as $ehash=>$cnt)
		{
			$fullr = str_replace("[COUNT-".$ehash."]", $cnt, $fullr);
		}

		/*foreach($scraperkeys as $ekey)
		{
			foreach($scrapers[$ekey]->ScrapeResults as $eres)
			{
				$trtmp = $SearchResultTemplate;
				
				
				$torpres = IsTorrentPresent($eres["link"]);
				if($torpres)
				{
					$trtmp = str_replace("[RESULTACTION]", $ActionNoneTemplate, $trtmp);
					$trtmp = str_replace("[RESULTCLASS]", "have", $trtmp);
				}
				else
				{
					$trtmp = str_replace("[RESULTACTION]", $ActionAddTemplate, $trtmp);
					
					$trtmp = str_replace("[RESULTCLASS]", "new", $trtmp);
				}
				
				$trtmp = str_replace("[NAME]", $eres["name"], $trtmp);
				$trtmp = str_replace("[DATE]", $eres["date"], $trtmp);
				$trtmp = str_replace("[SEEDCOUNT]", $eres["seed"], $trtmp);
				$trtmp = str_replace("[LEECHCOUNT]", $eres["leech"], $trtmp);
				$trtmp = str_replace("[SIZE]", $eres["size"], $trtmp);
				$trtmp = str_replace("[TORRENTURL]", $eres["link"], $trtmp);
				$trtmp = str_replace("[RESULTID]", $resultid, $trtmp);
				
				$fullr = $fullr.$trtmp;
				$resultid++;
			}
		}*/

		echo str_replace("[RESULTROWS]", $fullr, $this->SearchResultOutline);
	}

	function IsTorrentPresent($torrentlink)
	{
		
		
		if(!$this->tsm->result == "success") { return false; }
		
		$searchhash = str_replace("magnet:?xt=urn:btih:", "", $torrentlink);
		$searchhash = substr($searchhash, 0, strpos($searchhash, "&"));
		
		//echo "Checking Link: ".$torrentlink."<br />\n";
		foreach($this->tsm->arguments->torrents as $etorrent)
		{
			if(property_exists($etorrent, "magnetLink"))
			{
				$transtorhash = str_replace("magnet:?xt=urn:btih:", "", $etorrent->magnetLink);
				$transtorhash = substr($transtorhash, 0, strpos($transtorhash, "&"));
				
				//echo "\t".$etorrent->magnetLink."<br />\n";
				if(strtolower($searchhash) == strtolower($transtorhash)) { return true; }
			}
		}
		
		return false;
	}

	function ParseHashFromMagnet($magnet)
	{
		$magnet = str_replace("magnet:?xt=urn:btih:", "", $magnet);
		return strtolower(substr($magnet, 0, strpos($magnet, "&")));
	}

}

?>

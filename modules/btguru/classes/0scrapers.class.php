<?//	Pi-NAS Component modbtguru Scraper
////////////////////////////////////////////////////////////////////////////////////
//
//	<tracker> search result scraper
//
//	this file is a class that is incomplete, but is the outline that
//	other scrapers need to follow to fit in nicely with the system
//
////////////////////////////////////////////////////////////////////////////////////
//
//	copyright 2011, 2012 brian murphy
//	www.gurudigitalsolutions.com
//
//	Originally created in the summer of 2011
//
//	Changes:
//
//	Mar 2, 2012
//		Added the MaxSearchResults field, and modified the ParsePage
//		method to limit parsing to the maximum result number.
//
////////////////////////////////////////////////////////////////////////////////////



abstract class btScraper
{
	abstract protected function SearchURL();
	public $SearchTerms = "";
	public $SearchResults = array();
	private $ResultsPage = "";
	
	private $ScraperName = "btScraper";
	private $ScraperVersion = "0.2";
	private $ScraperDate = "Mar-02-2012";
	public $ResultScoreMin = 130;
	
	public $TotalResults = 0;
	public $ScrapeResults = array();
	public $MaxSearchResults = 50;
	
	protected $EachResultHtml = array();
	
	function btScraper($query = "")
	{
		//
		//	This function takes the search query and returns the results.
		//
		
		if($query != "")
		{
			
			$this->SearchTerms = str_replace(" ", "+", $query);
			$this->PullPage();
		
			$this->ParsePage();
		}
	}
	
	public function PullPage()
	{
		//
		//	This function uses cURL to pull the results page.
		//
		
		//DebugMsg("Pulling search result page.", "1");
		//DebugMsg("Search query terms: ".$this->SearchTerms, "3");
		
		$cp = curl_init(str_replace("[SEARCHTERMS]", $this->SearchTerms, $this->SearchURL()));
		curl_setopt($cp, CURLOPT_HEADER, true);
		curl_setopt($cp, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($cp, CURLOPT_ENCODING, "identity");
		curl_setopt($cp, CURLOPT_USERAGENT, $this->ScraperName." v".$this->ScraperVersion." (".$this->ScraperDate.")");
		
		$this->ResultsPage = curl_exec($cp);
		curl_close($cp);
	}
	
	public function ParsePage()
	{
		//
		//	This function begins parsing the HTML that was returned
		//	from the search engine.
		//
		//	The goal is the find the total number of results,
		//	and also find each result to be further parsed
		
		$this->TotalResults = $this->ParseTotalResultCount($this->ResultsPage);
		$this->ParseIntoHtmlResults($this->ResultsPage);
		
		//echo "Results from here ".count($this->EachResultHtml)."\n";
		if(count($this->EachResultHtml) < $this->MaxSearchResults) { $maxresults = count($this->EachResultHtml); }
		else { $maxresults = $this->MaxSearchResults; }
		//DebugMsg("Parsing HTML Result page.  Max results: ".$maxresults, 3);
		
		for($eres = 0; $eres < $maxresults; $eres++)
		{
			//echo "Loopinng results ".$eres."\n";
			$torrentname = $this->ParseName($this->EachResultHtml[$eres]);
			$torrentsize = $this->ParseSize($this->EachResultHtml[$eres]);
			$torrentdate = $this->ParseDate($this->EachResultHtml[$eres]);
			$torrentseeds = $this->ParseSeedCount($this->EachResultHtml[$eres]);
			$torrentleeches = $this->ParseLeechCount($this->EachResultHtml[$eres]);
			$torrentlink = $this->ParseTorrentLink($this->EachResultHtml[$eres]);
			
			$this->AddResult($torrentname, $torrentlink, $torrentsize, $torrentdate, $torrentseeds, $torrentleeches);
		}
		
		
	}
	
	abstract protected function ParseTotalResultCount($html);
	abstract protected function ParseIntoHtmlResults($html);
	
	//	Parse out the name of the torrent
	abstract protected function ParseName($html);
	
	//	Parse out the total file size of the torrent
	abstract protected function ParseSize($html);
	
	//	Parse out the date the torrent was added
	abstract protected function ParseDate($html);
	
	//	Parse out the number of seeders
	abstract protected function ParseSeedCount($html);
	
	//	Parse the number of leechers
	abstract protected function ParseLeechCount($html);
	
	//	Parse out the link to the torrent
	abstract protected function ParseTorrentLink($html);
	
	
	protected function AddResult($torrentname, $torrentlink, $torrentsize, $torrentdate, $torrentseed, $torrentleech)
	{
		//
		//	This function will add this scraped result into the list of resultios.
		//

		if($torrentname == ""
		|| $torrentlink == ""
		|| $torrentsize == ""
		|| $torrentdate == ""
		|| $torrentseed == ""
		|| $torrentleech == "")
		{
			return false;
		}
		
		$thisresult = array(
			"name"=>$torrentname,
			"link"=>$torrentlink,
			"size"=>$torrentsize,
			"date"=>$torrentdate,
			"seed"=>$torrentseed,
			"leech"=>$torrentleech
		);
		//print_r($thisresult);
		$this->ScrapeResults[] = $thisresult;
		
		return true;
	}
	
	protected function BestUrl($croncheck = "")
	{
		//
		//	This function will choose the 'best' result and return it.
		//
		
		/*if(count($this->ScrapeResults) > 0)
		{
			if($this->ScrapeResults[0]["seed"] == 0 || strtoupper($this->ScrapeResults[0]["seed"]) == "X")
			{ return ""; }
			elseif(strtoupper($this->ScrapeResults[0]["leech"]) == "X")
			{ return ""; }
			
			if($croncheck != "")
			{
				if(str_replace($croncheck, "", $this->ScrapeResults[0]["name"]) == $this->ScrapeResults[0]["name"])
				{ return ""; }
			}
			
			return $this->ScrapeResults[0]["link"];
		}*/
		
		$br = $this->BestResult($croncheck);
		if($br > -1)
		{
			return $this->ScrapeResults[$br]["link"];
		}
		else
		{
			return "";
		}
	}
	
	protected function ResultTitle($croncheck)
	{
		$br = $this->BestResult($croncheck);
		if($br > -1)
		{
			return $this->ScrapeResults[$br]["name"];
		}
		else
		{
			return "";
		}
	}
	
	protected function BestResult($croncheck)
	{
		//
		//	This function will determine the 'best' result, and return the id
		//	of it.
		//
		
		$scrapescore = array();
		$highest = 0;
		
		if(count($this->ScrapeResults) > 0)
		{
			for($eres = 0; $eres < count($this->ScrapeResults); $eres++)
			{
				$scrapescore[$eres] = 100;
				if($this->ScrapeResults[$eres]["seed"] == 0 || strtoupper($this->ScrapeResults[$eres]["seed"]) == "X")
				{ $scrapescore[$eres] -= 50; }
				elseif(strtoupper($this->ScrapeResults[$eres]["leech"]) == "X")
				{ $scrapescore[$eres] -= 10; }
				
				if($croncheck != "") { $scrapescore[$eres] += $this->GetScoreForName($croncheck, $eres); }
				
				if($eres > 0)
				{
					if($scrapescore[$eres] > $scrapescore[$highest])
					{
						//	A new high-point total
						$highest = $eres;
					}
				}
			}
			
			echo "Scraper High Score: ".$scrapescore[$highest]."\n";
			
			if($scrapescore[$highest] < $this->ResultScoreMin) { return -1; }
			return $highest;
		}
		
		return -1;
	}
	
	protected function GetScoreForName($croncheck, $resultid)
	{
		if(str_replace($croncheck, "", $this->ScrapeResults[$resultid]["name"]) == $this->ScrapeResults[$resultid]["name"])
		{ return -20; }
		
		$toret = 10;
		
		$expol = explode("+", $this->SearchTerms);
		
		if(count($expol) > 0)
		{
			foreach($expol as $eterm)
			{
				if(str_replace(strtoupper($eterm), "", strtoupper($this->ScrapeResults[$resultid]["name"])) != strtoupper($this->ScrapeResults[$resultid]["name"]))
				{
					$toret += 15;
				}
				else
				{
					$toret -= 5;
				}
			}
		}
		
		return $toret;
	}
}



?>

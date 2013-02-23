<?//	Pi-NAS Component modbtguru Scraper etree
////////////////////////////////////////////////////////////////////////////////////
//
//	bt.etree.org search result scraper
//
//	this file is a class that will scrape bt.etree.org and return
//	all of the results for the search in an easily usable format.
//
////////////////////////////////////////////////////////////////////////////////////
//
//	copyright 2011, 2012 brian murphy
//	www.gurudigitalsolutions.com
//
////////////////////////////////////////////////////////////////////////////////////



class etree extends btScraper
{
	protected function SearchURL() { return "http://bt.etree.org/?searchss=[SEARCHTERMS]&cat=0"; }
	
	function etree($query = "")
	{
		//
		//	This function takes the search query and returns the results.
		//
		
		if($query != "")
		{
			$this->Run();
		}
		
		//print_r($this->ScrapeResults);
		//echo "Total Results: " . $this->TotalResults . "\n";
	}
	
	public function Run()
	{
		$this->SearchTerms = str_replace(" ", "+", $this->SearchTerms);
		$this->PullPage();
	
		$this->ParsePage();
	}
	
	protected function ParseTotalResultCount($html)
	{
		return -1;
	}
	
	protected function ParseIntoHtmlResults($html)
	{
		//	<tr align="right" bgcolor="#ffffff">
		$parts = explode("<tr align=\"right\" bgcolor=\"#ffffff\">", $html, 2);
		$parts = explode("<b>Filters:</b> &nbsp;", $parts[1]);
		$this->EachResultHtml = explode("<tr align=\"right\" bgcolor=\"#ffffff\">", $parts[0]);
		//echo "Parsed HTML into results (".count($this->EachResultHtml).")\n";
	}
	
	protected function ParseName($html)
	{
		//	details_link" href="details.php?id=
		$parts = explode("details_link\" href=\"details.php?id=", $html);
		$parts = explode("</b></a>", $parts[1]);
		$parts = explode("><b>", $parts[0]);
		return $parts[1];
	}
	
	protected function ParseSize($html)
	{
		$parts = explode(" MB</td>", $html);
		
		return substr($parts[0], strrpos($parts[0], ">") + 1);
	}
	
	protected function ParseDate($html)
	{
		$parts = explode("#startcomments\">", $html);
		$parts = explode("<td>", $parts[1]);
		$parts = explode(" ", $parts[1]);
		return $parts[0];
	}
	
	protected function ParseSeedCount($html)
	{
		$parts = explode("#seeders\">", $html);
		$parts = explode("<", $parts[1]);
		return $parts[0];
	}
	
	protected function ParseLeechCount($html)
	{
		$parts = explode("#leechers\">", $html);
		$parts = explode("<", $parts[1]);
		return $parts[0];
	}
	
	protected function ParseTorrentLink($html)
	{
		$parts = explode("download.php", $html);
		$parts = explode("\"><img", $parts[1]);
		
		return "http://bt.etree.org/download.php".$parts[0];
	}
}



?>

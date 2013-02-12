<?//	Pi-NAS Component modbtguru Scraper tpb
/////////////////////////////////////////////////////////////////////////////
//
//	the pirate bay search result scraper
//
//	This class will search The Pirate Bay and return the results.
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2012, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class tpb extends btScraper
{
	protected function SearchURL() { return "http://thepiratebay.se/search/[SEARCHTERMS]/0/7/0"; }
	
	function tpb($query = "")
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
		$this->SearchTerms = str_replace(" ", "%20", $this->SearchTerms);
		$this->PullPage();
	
		$this->ParsePage();
	}
	
	protected function ParseTotalResultCount($html)
	{
		return -1;
	}
	
	protected function ParseIntoHtmlResults($html)
	{
		//	<div class="detName"
		$parts = explode("\"detName\">", $html, 2);
		$parts = explode("</table>", $parts[1], 2);
		$this->EachResultHtml = explode("\"detName\">", $parts[0]);
		
		//echo "Parsed HTML into results (".count($this->EachResultHtml).")\n";
	}
	
	protected function ParseName($html)
	{
		$parts = explode("\">", $html, 2);
		$parts = explode("</a>", $parts[1]);
		return $parts[0];

	}
	
	protected function ParseSize($html)
	{
		$parts = explode("detDesc\">Uploaded", $html);
		$parts = explode(", Size ", $parts[1]);
		$parts = explode(",", $parts[1]);
		$ssz = str_replace("&nbsp;", " ", $parts[0]);
		
		
		return $ssz;
	}
	
	protected function ParseDate($html)
	{
		$parts = explode("detDesc\">Uploaded", $html);
		$parts = explode("&nbsp;", $parts[1]);
		
		$ssz = str_replace(" ", "", $parts[0]);
		if(str_replace("<b>", "", $ssz) != $ssz)
		{
			return str_replace("<b>", "", $ssz)." m";
		}
		return str_replace(" ", "", $parts[0]);

	}
	
	protected function ParseSeedCount($html)
	{
		$parts = explode("<td align=\"right\">", $html);
		$parts = explode("</td>", $parts[1]);
		return $parts[0];
		
	}
	
	protected function ParseLeechCount($html)
	{
		$parts = explode("<td align=\"right\">", $html);
		$parts = explode("</td>", $parts[2]);
		return $parts[0];

	}
	
	protected function ParseTorrentLink($html)
	{
		$parts = explode("magnet:", $html);
		$parts = explode("\"", $parts[1]);
		return "magnet:".$parts[0];

	}
}

?>

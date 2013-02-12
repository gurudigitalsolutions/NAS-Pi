<?//	Pi-NAS Component modbtguru Scraper kat
/////////////////////////////////////////////////////////////////////////////
//
//	kickass torrents search result scraper
//
//	This class will search KickassTorrents and return the results.
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2012, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class kat extends btScraper
{
	protected function SearchURL() { return "http://kat.ph/usearch/[SEARCHTERMS]/"; }
	
	function kat($query = "")
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
		//$this->SearchTerms = str_replace(" ", "%20", $this->SearchTerms);
		$this->PullPage();
	
		$this->ParsePage();
	}
	
	protected function ParseTotalResultCount($html)
	{
		return -1;
	}
	
	protected function ParseIntoHtmlResults($html)
	{
		//echo str_replace("<", "&lt;", $html); exit;
		$parts = explode("<tr class=\"firstr\">", $html, 2);
		$parts = explode("</tr>", $parts[1], 2);
		$parts = explode("</table>", $parts[1], 2);
		$this->EachResultHtml = explode("</tr>", $parts[0]);
		
		//echo "Parsed HTML into results (".count($this->EachResultHtml).")\n";
	}
	
	protected function ParseName($html)
	{
		$parts = explode("class=\"torrentname\">", $html);
		$parts = explode("</a>", $parts[1]);
		$parts = explode("plain bold\">", $parts[1]);
		$parts = explode("</a>", $parts[1]);
		$pn = $parts[0];
		$pn = str_replace("<strong class=\"red\">", "", $pn);
		$pn = str_replace("</strong>", "", $pn);
		return $pn;


	}
	
	protected function ParseSize($html)
	{
		$parts = explode("nobr center\">", $html);
		$parts = explode("</span>", $parts[1]);
		return  str_replace("<span>", "", $parts[0]);
		

	}
	
	protected function ParseDate($html)
	{
		$parts = explode("class=\"center\">", $html);
		$parts = explode("</td>", $parts[count($parts) - 1]);
		return str_replace("&nbsp;", "", $parts[0]);


	}
	
	protected function ParseSeedCount($html)
	{
		$parts = explode("<td class=\"green center\">", $html);
		$parts = explode("</td>", $parts[1]);
		return $parts[0];
		
	}
	
	protected function ParseLeechCount($html)
	{
		$parts = explode("<td class=\"red lasttd center\">", $html);
		$parts = explode("</td>", $parts[1]);
		return $parts[0];
		


	}
	
	protected function ParseTorrentLink($html)
	{
		$parts = explode("magnet:?xt", $html);
		$parts = explode("\"", $parts[1]);
		return "magnet:?xt".$parts[0];
		

	}
}

?>

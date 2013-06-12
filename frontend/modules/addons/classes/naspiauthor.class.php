<?//	Pi-NAS Component modAddOns NASPiAuthor
/////////////////////////////////////////////////////////////////////////////////////
//
//	This class represents an author from the NAS-Pi Add On Repo
//
/////////////////////////////////////////////////////////////////////////////////////

class NASPiAuthor
{
	public $AuthorID = 0;
	public $AuthorName = "";
	public $TimeCreated = 0;
	public $LastUpdated = 0;
	public $URL = "";
	public $Email = "";
	
	public function Load($aid)
	{
		$query = "SELECT * FROM authors WHERE id='".$aid."' LIMIT 1";
		$arz = mysql_query($query);
		
		if(mysql_numrows($arz) == 0) { return false; }
		
		$this->AuthorID = $aid;
		$this->AuthorName = stripslashes(mysql_result($arz, 0, "authorname"));
		$this->TimeCreated = mysql_result($arz, 0, "timecreated");
		$this->LastUpdated = mysql_result($arz, 0, "lastupdated");
		$this->URL = stripslashes(mysql_result($arz, 0, "url"));
		$this->Email = stripslashes(mysql_result($arz, 0, "email"));
	}
}

?>

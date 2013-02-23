<?/////////////////////////////////////////////////////////////////////////////
//
//	User Representation
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class UserAccount
{
	public $Username = "";
	public $Password = "";
	public $Salt = "";
	public $LastActive = "";
	public $Groups = array("user");
	public $SessionID = "";
	public $Email = "";
	public $AllowMultipleIPsPerSession = true;
	
	function Save()
	{
		file_put_contents(MODULEPATH."/users/accounts/".$this->Username, serialize($this));
		return true;
	}
	
	function GroupMember($group)
	{
		if(count($this->Groups) == 0) { return false; }
		
		foreach($this->Groups as $eg)
		{
			if(strtolower($eg) == strtolower($group)) { return true; }
		}
		
		return false;
	}
	
	function GroupMemberOfAny($groups)
	{
		foreach($groups as $egroup)
		{
			if($this->GroupMember($egroup)) { return true; }
		}
		
		return false;
	}
}
?>

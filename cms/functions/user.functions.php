<?/////////////////////////////////////////////////////////////////////////////
//
//	Pi-NAS User Account Functions
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

function CheckLogin()
{
	global $CurrentUser;
	global $CurrentSession;
	global $CurrentSessionData;
	
	if($_COOKIE['sid'] != "")
	{
		$insid = $_COOKIE['sid'];
		if(strpos($insid, ".") > -1) { return false; }
		if(strlen($insid) > 128) { return false; }
		
		if(!file_exists(MODULEPATH."/users/sessions/".$insid)) { return false; }
		
		$tsess = unserialize(file_get_contents(MODULEPATH."/users/sessions/".$insid));
		$tusr = unserialize(file_get_contents(MODULEPATH."/users/accounts/".$tsess->Username));
		
		if($tsess->IP == $_SERVER['REMOTE_ADDR']
		|| $tusr->AllowMultipleIPsPerSession)
		{
			$CurrentUser = $tusr;
			$CurrentUser->LastActive = time();
			$CurrentUser->Save();
			
			$tsess->LastActive = time();
			$tsess->IP = $_SERVER['REMOTE_ADDR'];
			$tsess->Save();
			
			$CurrentSessionData = $tsess;
			
			$CurrentSession = $insid;
			define("USERLOGGEDIN", true);
			return true;
		}
		
	}
	
	return false;
}

?>

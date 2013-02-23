<?//	Pi-NAS Module users modUsers
///////////////////////////////////////////////////////////////////////////////
//
//	Pi-NAS Users Module
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class modUsers extends PiNASModule
{
	
	public function Initialize()
	{
		$this->ModuleCode = "users";
		$this->MenuTitle = "Users";
		
		$this->AuthRequired = true;
		$this->AllowGroups = array("user", "admin");
		
		//$this->AddSubMenu("config", "Configuration", true, array("admin"));
		$this->AddSubMenu("myaccount", "My Account");
		$this->AddSubMenu("manager", "User Manager", true, array("admin", "usermanager"));
		
		$this->AddSubAuth("adduser", array("admin", "usermanager"));
		$this->AddSubAuth("addgroup", array("admin", "usermanager"));
		$this->AddSubAuth("updateuser", array("admin", "usermanager"));
		$this->AddSubAuth("moreinfo", array("admin", "usermanager"));
		
		if(filesize(MODULEPATH."/users/groups.txt") < 5)
		{
			file_put_contents(MODULEPATH."/users/groups.txt", serialize(array("admin", "user")));
		}
	}
	
	public function Render()
	{
		global $StyleSheets; $StyleSheets[] = "users";
		global $Scripts; $Scripts[] = "users";
		global $Modules;
		global $CurrentUser;
		global $RequestVars;
		
		if($RequestVars["sub"] == "") { $RequestVars["sub"] = "myaccount"; }
		
		$toret = "";
		
		if(!USERLOGGEDIN && $RequestVars["sub"] == "login") { $this->AttemptLogin(); }
		else if(USERLOGGEDIN && $RequestVars["sub"] == "logout") { $this->Logout(); }
		
		if($RequestVars["sub"] == "manager")
		{
			$ManagerTemplate = file_get_contents(MODULEPATH."/users/templates/manager.html");
			$EachUserTemplate = file_get_contents(MODULEPATH."/users/templates/manager-eachuser.html");
			$EachGroupTemplate = file_get_contents(MODULEPATH."/users/templates/manager-eachgroup.html");
			
			$ttlusers = "";
			$userfiles = scandir(MODULEPATH."/users/accounts");
			foreach($userfiles as $euf)
			{
				if($euf != "." && $euf != "..")
				{
					$ttmp = $EachUserTemplate;
					$tusr = unserialize(file_get_contents(MODULEPATH."/users/accounts/".$euf));
					
					$ttmp = str_replace("[USERNAME]", $euf, $ttmp);
					$ttmp = str_replace("[EMAIL]", $tusr->Email, $ttmp);
					$ttmp = str_replace("[TIMECREATED]", date("m-d-y H:i:s", filemtime(MODULEPATH."/users/accounts/".$euf)), $ttmp);
					
					$ttlusers = $ttlusers.$ttmp;
				}
			}
			
			$toret = str_replace("[EACHUSER]", $ttlusers, $ManagerTemplate);
			
			$groupnames = array();
			$ttlgroups = "";
			$groups = unserialize(file_get_contents(MODULEPATH."/users/groups.txt"));
			foreach($groups as $eg)
			{
				$tgtmp = $EachGroupTemplate;
				$tgtmp = str_replace("[GROUPNAME]", $eg, $tgtmp);
				$ttlgroups = $ttlgroups.$tgtmp;
				
				$groupnames[$eg] = true;
			}
			
			foreach($Modules as $emkey=>$emod)
			{
				foreach($emod->SubMenus as $eskey=>$esub)
				{
					foreach($esub["authgroups"] as $egroup)
					{
						if(!array_key_exists($egroup, $groupnames))
						{
							$tgtmp = $EachGroupTemplate;
							$tgtmp = str_replace("[GROUPNAME]", $egroup, $tgtmp);
							$ttlgroups = $ttlgroups.$tgtmp;
							$groupnames[$egroup] = true;
						}
					}
				}
			}
			
			$toret = str_replace("[EACHGROUP]", $ttlgroups, $toret);
		}
		else if($RequestVars["sub"] == "adduser")
		{
			$un = $RequestVars["username"];
			$pw = $RequestVars["password"];
			$pwc = $RequestVars["passwordconf"];
			$email = $RequestVars["email"];
			
			$un = str_replace("..", ".", $un);
			$un = str_replace("/", "", $un);
			$un = str_replace("\\", "", $un);
			$un = str_replace("\"", "", $un);
			$un = str_replace("'", "", $un);
			$un = str_replace("`", "", $un);
			$un = str_replace("|", "", $un);
			$un = str_replace("<", "", $un);
			$un = str_replace(">", "", $un);
			
			if(strlen($un) > 2
			&& $pw == $pwc)
			{
				$nuser = new UserAccount();
				$nuser->Username = $un;
				$nuser->Email = $email;
				
				$rndstr = "abcdefghijklmnopqrstuvwxyz0123456789";
				$salt = "";
				for($i = 0; $i < 10; $i++) { $salt = $salt.substr($rndstr, rand(0, strlen($rndstr) - 1), 1); }
				
				$nuser->Salt = $salt;
				$nuser->Password = md5($pw.$salt);
				
				$nuser->Save();
				
				header("Location: /?module=users&sub=manager");
				exit;
			}
			
			//echo $RequestVars["username"]."<br />".$un."<br />".$pw."<br />".$pwc."<br />".$email;
			//exit;
			header("Location: /?module=users&sub=manager");
			exit;
		}
		else if($RequestVars["sub"] == "addgroup")
		{
			$groups = unserialize(file_get_contents(MODULEPATH."/users/groups.txt"));
			$ngname = $RequestVars["groupname"];
			
			if(strlen($ngname) > 2)
			{
				foreach($groups as $eg)
				{
					if(strtolower($eg) == strtolower($ngname))
					{
						header("Location: /?module=users&sub=manager");
						exit;
					}
				}
				
				$groups[] = $ngname;
				file_put_contents(MODULEPATH."/users/groups.txt", serialize($groups));
			}
			
			header("Location: /?module=users&sub=manager");
			exit;
		}
		else if($RequestVars["sub"] == "moreinfo")
		{
			$username = $RequestVars["username"];
			if(!file_exists(MODULEPATH."/users/accounts/".$username)) { echo "User not found."; exit; }
			
			$tusr = unserialize(file_get_contents(MODULEPATH."/users/accounts/".$username));
			
			$MoreInfoTemplate = file_get_contents(MODULEPATH."/users/templates/manager-moreinfo.html");
			$EachGroupTemplate = file_get_contents(MODULEPATH."/users/templates/manager-moreinfo-eachgroup.html");
			
			$groups = unserialize(file_get_contents(MODULEPATH."/users/groups.txt"));
			
			$groupnames = array();
			$ttlgroups = "";
			foreach($groups as $eg)
			{
				$tgroup = $EachGroupTemplate;
				
				$tgroup = str_replace("[GROUPNAME]", $eg, $tgroup);
				if($tusr->GroupMember($eg)) { $tgroup = str_replace("[ENABLED]", "checked=checked", $tgroup); }
				else { $tgroup = str_replace("[ENABLED]", "", $tgroup); }
				
				$ttlgroups = $ttlgroups.$tgroup;
				$groupnames[$eg] = true;
			}
			
			foreach($Modules as $emkey=>$emod)
			{
				foreach($emod->SubMenus as $eskey=>$esub)
				{
					foreach($esub["authgroups"] as $egroup)
					{
						if(!array_key_exists($egroup, $groupnames))
						{
							$tgtmp = $EachGroupTemplate;
							$tgtmp = str_replace("[GROUPNAME]", $egroup, $tgtmp);
							$ttlgroups = $ttlgroups.$tgtmp;
							$groupnames[$egroup] = true;
						}
					}
				}
			}
			
			$MoreInfoTemplate = str_replace("[EACHGROUP]", $ttlgroups, $MoreInfoTemplate);
			$MoreInfoTemplate = str_replace("[USERNAME]", $tusr->Username, $MoreInfoTemplate);
			
			echo $MoreInfoTemplate;
			exit;
		}
		else if($RequestVars["sub"] == "updateuser")
		{
			$username = $RequestVars["username"];
			if(!file_exists(MODULEPATH."/users/accounts/".$username)) { header("Location: /?module=users&sub=manager"); exit; }
			
			$newpass = $RequestVars["newpass"];
			$newpassconf = $RequestVars["newpassconf"];
			
			$tusr = unserialize(file_get_contents(MODULEPATH."/users/accounts/".$username));
			$groups = unserialize(file_get_contents(MODULEPATH."/users/groups.txt"));
			
			$tusr->Groups = array();
			
			foreach($groups as $eg)
			{
				if(strtolower($eg) == "admin" && strtolower($tusr->Username) == "admin")
				{
					$tusr->Groups[] = $eg;
				}
				else
				{
					if($RequestVars["group_".$eg] != "") { $tusr->Groups[] = $eg; }
				}
			}
			
			if($newpass != "")
			{
				if($newpass == $newpassconf)
				{
					$tusr->Password = md5($newpass.$tusr->Salt);
				}
			}
			
			$tusr->Save();
			
			header("Location: /?module=users&sub=manager");
			exit;
		}
		else if($RequestVars["sub"] == "myaccount")
		{
			$MyAccountTemplate = file_get_contents(MODULEPATH."/users/templates/myaccount.html");
			$EachGroupTemplate = file_get_contents(MODULEPATH."/users/templates/myaccount-eachgroup.html");
			
			$ttlgroups = "";
			foreach($CurrentUser->Groups as $eg)
			{
				$tgroup = $EachGroupTemplate;
				
				$tgroup = str_replace("[GROUPNAME]", $eg, $tgroup);
				$ttlgroups = $ttlgroups.$tgroup;
			}
			
			$MyAccountTemplate = str_replace("[EACHGROUP]", $ttlgroups, $MyAccountTemplate);
			$MyAccountTemplate = str_replace("[USERNAME]", $CurrentUser->Username, $MyAccountTemplate);
			$MyAccountTemplate = str_replace("[EMAIL]", $CurrentUser->Email, $MyAccountTemplate);
			$MyAccountTemplate = str_replace("[SESSIONID]", $CurrentSession, $MyAccountTemplate);
			
			$toret= $MyAccountTemplate;
		}
		else
		{
			$toret = "Oi";
		}
		
		return $toret;
	}
	
	public function AttemptLogin()
	{
		global $CurrentSession;
		global $RequestVars;
		
		if(USERLOGGEDIN)
		{
			header("Location: /");
			exit;
		}
		
		$un = $RequestVars["username"];
		$pw = $RequestVars["password"];
		
		if($un == "." || $un == ".." || strpos($un, "/") > -1)
		{
			header("Location: /");
			exit;
		}
		
		if(file_exists(MODULEPATH."/users/accounts/".$un))
		{
			$tusr = unserialize(file_get_contents(MODULEPATH."/users/accounts/".$un));
			if(md5($pw.$tusr->Salt) == $tusr->Password)
			{
				$chars = "abcdefghijklmnopqrstuvwxyz1234567890";
				$tsid = "";
				for($i = 0; $i < 24; $i++)
				{
					$tsid = $tsid.substr($chars, rand(0, 35), 1);
				}
				
				$usersid = new UserSession();
				$usersid->ID = $tsid;
				$usersid->Username = $un;
				$usersid->IP = $_SERVER['REMOTE_ADDR'];
				$usersid->LastActive = time();
				$usersid->Save();
				
				header("Location: /");
				setcookie("sid", $tsid, time() + (60 * 60 * 24 * 365));
				exit;
			}
		}
		
		header("Location: /");
		exit;
	}
	
	public function Logout()
	{
		global $CurrentSession;
		
		if(!USERLOGGEDIN)
		{
			header("Location: /");
			exit;
		}
		
		unlink(MODULEPATH."/users/sessions/".$CurrentSession);
		setcookie("sid", "", time() - 3600);
		header("Location: /");
		exit;
	}
}

?>

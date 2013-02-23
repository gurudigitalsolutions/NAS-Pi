<?/////////////////////////////////////////////////////////////////////////////
//
//	NAS-Pi CMS
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

if(!defined("INPI")) { exit; }

//	Things to do:
//		Load Helper Functions
//		Load Helper Classes
//		Determine if/who is logged in
//		Setup templating
//		Run submodule
//		Build Page

$Config = "";
$StyleSheets = array("sitetemplate");
$Scripts = array("gds");
$PageTitle = array("NAS-Pi");
$PageTitleSpacer = " :: ";
$CurrentUser = "";
$CurrentSession = "";
$CurrentSessionData = "";
$SiteTemplate = file_get_contents(PUBLICHTMLPATH."/template.html");
$MainMenuItemTemplate = file_get_contents(CMSPATH."/templates/menu-eachitem.html");
$MainMenuSubItemTemplate = file_get_contents(CMSPATH."/templates/menu-eachsubitem.html");
$LoginBoxLoggedInTemplate = file_get_contents(CMSPATH."/templates/loginbox-loggedin.html");
$LoginBoxLoggedOutTemplate = file_get_contents(CMSPATH."/templates/loginbox-loggedout.html");
$Modules = array();
$RequestVars = array();

if(!IncludeDir(FUNCTIONPATH) || !IncludeDir(CLASSPATH)) { echo "Smells like rotten fruit :("; exit; }

if(file_exists(CMSPATH."/data/cms.cfg")) { $Config = unserialize(file_get_contents(CMSPATH."/data/cms.cfg")); }
else { $Config = new NASPiConfig(); }

require_once(MODULEPATH."/module-base.class.php");
if(!IncludeDir(MODULEPATH, "ImportModule")) { echo "Modular decay :("; exit; }


if(!file_exists(MODULEPATH."/users/accounts/admin"))
{
	$tusr = new UserAccount();
	$tusr->Username = "admin";
	$tusr->Salt = "sdflkej44";
	$tusr->LastActive = 0;
	$tusr->Email = "";
	$tusr->Password = md5("password".$tusr->Salt);
	$tusr->Groups[] = "admin";
	$tusr->Save();
}
//include(CMSPATH."login.php");

//	Determine which module we are running and render the page content.
if($_SERVER['REQUEST_METHOD'] == "GET") { $RequestVars = $_GET; }
else if($_SERVER['REQUEST_METHOD'] == "POST") { $RequestVars = $_POST; }

if(!CheckLogin())
{
	define("USERLOGGEDIN", false);
}

$mod = "home";
if(array_key_exists("module", $RequestVars))
{
	if(array_key_exists($RequestVars["module"], $Modules)) { $mod = $RequestVars["module"]; }
}

$pagecontent = "";
if($mod == "home") { $pagecontent = "Pi-NAS"; }
else
{
	if($Config->IsModuleEnabled($mod))
	{
		$sub = $RequestVars["sub"];
		
		if($sub == "") { $pagecontent = $Modules[$mod]->Render(); }
		else
		{
			if(!array_key_exists($sub, $Modules[$mod]->SubAuthList))
			{ $pagecontent = $Modules[$mod]->Render(); }
			else
			{
				if(!USERLOGGEDIN) { $pagecontent = "You must be logged in to do that."; }
				else
				{
					if($CurrentUser->GroupMemberOfAny($Modules[$mod]->SubAuthList[$sub]))
					{
						$pagecontent = $Modules[$mod]->Render();
					}
					else
					{
						$pagecontent = "Permission denied.";
					}
				}
			}
		}
	}
	else
	{
		//	Module is not enabled.
		$pagecontent = "That module is not enabled.";
	}
}
$SiteTemplate = str_replace("[PAGECONTENT]", $pagecontent, $SiteTemplate);

//	Add stylesheets
$ssline = "";
foreach($StyleSheets as $ess)
{
	$ssline = $ssline."<link href=\"/stylesheets/".$ess.".css\" rel=\"stylesheet\" type=\"text/css\" />\n";
}
$SiteTemplate = str_replace("[STYLESHEETS]", $ssline, $SiteTemplate);

//	Add Javascript
$ssline = "";
foreach($Scripts as $ess)
{
	$ssline = $ssline."<script src=\"/scripts/".$ess.".js\"></script>\n";
}
$SiteTemplate = str_replace("[SCRIPTS]", $ssline, $SiteTemplate);

//	Format the page title
$ptitle = "";
$ptloops = 0;
foreach($PageTitle as $ept)
{
	$ptitle = $ptitle.$ept.$PageTitleSpacer;
	$ptloops++;
}
if($ptloops > 0) { $ptitle = substr($ptitle, 0, strlen($ptitle) - strlen($PageTitleSpacer)); }
$SiteTemplate = str_replace("[PAGETITLE]", $ptitle, $SiteTemplate);


//	Build the menu
$menutemplate = "";

foreach($Modules as $emkey=>$emval)
{
	if($Config->IsModuleEnabled($emkey))
	{
		if($emval->MenuDisplay == true)
		{
			if($emval->AuthRequired == false
			|| ($emval->AuthRequired == true && USERLOGGEDIN && $CurrentUser->GroupMemberOfAny($emval->AllowGroups)))
			{
				$tmenu = $MainMenuItemTemplate;
				$tmenu = str_replace("[MENUTITLE]", $emval->MenuTitle, $tmenu);
				$tmenu = str_replace("[MODULECODE]", $emval->ModuleCode, $tmenu);
				
				if(count($emval->SubMenus) > 0)
				{
					$tsubtmp = "";
					foreach($emval->SubMenus as $smkey=>$smval)
					{
						if($smval["authrequired"] == false
						|| ($smval["authrequired"] == true && USERLOGGEDIN && $CurrentUser->GroupMemberOfAny($smval["authgroups"])))
						{
							$tsubs = $MainMenuSubItemTemplate;
							
							$tsubs = str_replace("[MODULECODE]", $emval->ModuleCode, $tsubs);
							$tsubs = str_replace("[SUBCODE]", $smkey, $tsubs);
							$tsubs = str_replace("[SUBTITLE]", $smval["title"], $tsubs);
							$tsubtmp = $tsubtmp.$tsubs;
						}
					}
					$tmenu = str_replace("[SUBMENUS]", $tsubtmp, $tmenu);
				}
				else
				{
					$tmenu = str_replace("[SUBMENUS]", "", $tmenu);
				}
				
				$menutemplate = $menutemplate.$tmenu;
			}
		}
	}
}
$SiteTemplate = str_replace("[MAINMENU]", $menutemplate, $SiteTemplate);

//	Build the login box
if(USERLOGGEDIN)
{
	$SiteTemplate = str_replace("[LOGINBOX]", str_replace("[USERNAME]", $CurrentUser->Username, $LoginBoxLoggedInTemplate), $SiteTemplate);
}
else
{
	$SiteTemplate = str_replace("[LOGINBOX]", $LoginBoxLoggedOutTemplate, $SiteTemplate);
}

echo $SiteTemplate;

function IncludeDir($path, $callfunction = "")
{
	if(!is_dir($path)) { return false; }
	
	
	$sdirs = scandir($path);
	
	foreach($sdirs as $edir)
	{
		if($edir != "." && $edir != "..")
		{
			if(is_dir($path."/".$edir)) { IncludeDir($path."/".$edir, $callfunction); }
			else
			{
				if($callfunction == "") { require_once($path."/".$edir); }
				else { $callfunction($path."/".$edir); }
			}
		}
	}
	
	/*
	 * //$dh = opendir($path);
	 * while(false !== ($edir = readdir($dh)))
	{
		if($edir != "." && $edir != "..")
		{
			if(is_dir($path."/".$edir)) { IncludeDir($path."/".$edir, $callfunction); }
			else
			{
				if($callfunction == "") { require_once($path."/".$edir); }
				else { $callfunction($path."/".$edir); }
			}
		}
	}
	
	closedir($dh);*/
	
	return true;
}

function ImportModule($path)
{
	global $Modules;
	
	if(!is_file($path)) { error_log("Pi-NAS: ImportModule: Not a file: ".$path); return false; }
	if($path == MODULEPATH."/module-base.class.php") { return false; }
	
	if(substr($path, strlen($path) - 10) != ".class.php") { return false; }
	
	$fh = fopen($path, "r");
	$fline = fgets($fh);
	fclose($fh);
	
	$fline = str_replace("<?", "", $fline);
	$fline = str_replace("//", "", $fline);
	$fline = trim($fline);
	
	//	The first line of the module needs to be at least
	//		Pi-NAS Module x x
	
	if(strlen($fline) < 17) { error_log("Pi-NAS: ImportModule: Less than 17 chars: ".$fline); return false; }
	$parts = explode(" ", $fline);
	if(count($parts) < 4) { error_log("Pi-NAS: ImportModule: Module Description line has less than 4 parts. ".$fline); return false; }
	
	if(strtolower($parts[0]) != "pi-nas") { error_log("Pi-NAS: ImportModule: Not == pi-nas: ".$parts[0]); return false; }
	if(strtolower($parts[1]) != "module" && strtolower($parts[1]) != "component") { error_log("Pi-NAS: ImportModule: Not == module / component: ".$parts[1]); return false; }
	
	//$Modules[$parts[2]] = $parts[3];
	require_once($path);
	
	if(strtolower($parts[1]) == "module")
	{
		$Modules[$parts[2]] = new $parts[3]();
		$Modules[$parts[2]]->Initialize();
	}
	
	return true;
}



?>

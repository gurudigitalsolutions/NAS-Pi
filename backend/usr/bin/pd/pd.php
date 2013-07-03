#!/usr/bin/php
<?///////////////////////////////////////////////////////////////////////////////////
//
//	NAS-Pi Test Daemon
//
/////////////////////////////////////////////////////////////////////////////////////

define("BASECODEPATH", "/tmp/usr/share/naspi");
define("PUBLICHTMLPATH", BASECODEPATH."/public_html");
define("MODULEPATH", BASECODEPATH."/modules");
define("CMSPATH", BASECODEPATH."/cms");
define("MODULECONFIGPATH", BASECODEPATH."/addons/modules");

$sockDir = "/tmp/naspi/pd";
$sockFile = "pd.sock";
$ModulesPath = "/home/guru/Code/NAS-Pi/backend/usr/bin/pd/modules";

$Version = "0.2013.07.02";
$Modules = array();
LoadModules();

if(!file_exists($sockDir)) { `mkdir $sockDir -p`; }
if(file_exists($sockDir."/".$sockFile)) { `rm $sockDir."/".$sockFile`; }

$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);

socket_bind($socket, $sockDir."/".$sockFile);
socket_listen($socket);

socket_select($temp = array($socket), $temp = null, $temp = null, 20);



$KeepRunning = true;
while($KeepRunning)
{
	$client = socket_accept($socket);
	//socket_set_nonblock($client);
	
	$pid = pcntl_fork();
	if($pid == -1) { echo "The server could not for a child process.\n"; exit; }
	else if($pid == 0)
	{
		//	If $pid is 0, this is the child process
		DoConnection($client);
	}
	else
	{
		//	This is the main server.  Just need to start listening again.
		socket_listen($socket);
	}

}

//socket_close($client);
socket_close($socket);



function ParseCLI()
{
	
}

function DoConnection($client)
{
	global $Version;
	global $Modules;
	
	$KeepRunning = true;
	while($KeepRunning)
	{
		$buffer = socket_read($client, 2048, PHP_NORMAL_READ);
		
		if(strlen($buffer) == 0) { $KeepRunning = false; }
		else if($buffer === false)
		{
			echo "\tRead buffer returned false.\n";
			$KeepRunning = false;
		}
		else
		{
			$buffer = trim($buffer);
			echo $buffer."\n";
			
			if($buffer == "help")
			{
				$msg = "NAS-Pi pd v".$Version."\n".
						"Commands: help, exit, mod <modcode> <mod arguments>\n";
				socket_write($client, $msg, strlen($msg));
			}
			else if($buffer == "exit")
			{
				$msg = "BYE\n";
				socket_write($client, $msg, strlen($msg));
				$KeepRunning = false;
			}
			else if(strlen($buffer) > 8 && substr($buffer, 0, 4) == "mod ")
			{
				$msg = "FAIL Nothing processed";
				
				$cmdparts = explode(" ", $buffer);
				$modcode = $cmdparts[1];
				
				$modargs = array();
				if(count($cmdparts) > 2)
				{
					for($ecp = 2; $ecp < count($cmdparts); $ecp++)
					{
						$modargs[] = $cmdparts[$ecp];
					}
				}
				
				if(array_key_exists($modcode, $Modules))
				{
					$modMod = new $Modules[$modcode]();
					$modMod->Initialize();
					$msg = $modMod->ProcessCommand($modargs);
				}
				else
				{
					$msg = "Module not loaded";
				}
				//$msg = "Piping ".count($modargs)." arguments to mod ".$modcode."\n";
				
				$msg = $msg."\n";
				socket_write($client, $msg, strlen($msg));
			}
		}
	}
	
	echo "**** DoConnection Ending ****\n\n";
	socket_close($client);
	exit(0);
}


function LoadModules()
{
	global $Modules;
	global $ModulesPath;
	
	if(file_exists($ModulesPath."/modules.class.php")) { include_once($ModulesPath."/modules.class.php"); }
	
	ImportModules($ModulesPath);
}

function ImportModules($path)
{
	global $Modules;
	
	$stuff = scandir($path);
	foreach($stuff as $efile)
	{
		if($efile != "." && $efile != "..")
		{
			if(is_dir($path."/".$efile)) { ImportModules($path."/".$efile); }
			else
			{
				if(strtolower(substr($efile, strlen($efile) - 10)) == ".class.php")
				{
					$fh = fopen($path."/".$efile, "r");
					$fline = fgets($fh);
					fclose($fh);
					
					$fline = trim(substr($fline, 4));
					$fprts = explode(" ", $fline);
					
					if(count($fprts) >= 4)
					{
						if(strtolower($fprts[0]) == "nas-pi" || strtolower($fprts[0]) == "pi-nas")
						{
							if(strtolower($fprts[1]) == "module")
							{
								$Modules[$fprts[2]] = $fprts[3];
								include_once($path."/".$efile);
							}
							else if(strtolower($fprts[1]) == "component")
							{
								include_once($path."/".$efile);
							}
						}
					}
				}
			}
		}
	}
}
?>

<?///////////////////////////////////////////////////////////////////////////////////
//
//	Mount Checker / Maintainer (for root)
//
/////////////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2012 Brian Murphy
//	www.gurudigitalsolutions.com
//
/////////////////////////////////////////////////////////////////////////////////////

chdir("/home/media/pinas/modules/files/sources");

require_once("0source.class.php");
require_once("source-ftp.class.php");
require_once("source-local.class.php");
require_once("source-smb.class.php");
require_once("source-sshfs.class.php");



$dh = opendir("data");
while(false !== ($edir = readdir($dh)))
{
	if($edir != "." && $edir != "..")
	{
		$src = unserialize(file_get_contents("data/".$edir));
		
		if(!file_exists("/media/".$edir)) 
		{
			mkdir("/media/".$edir);
			`chown media:media /media/$edir`;
		}
		
		if(!$src->Enabled)
		{
			$mntgrep = "\"on /media/".$edir."\"";
			$mntout = trim(`mount -l | grep $mntgrep`);
			if($mntout != "") { `umount /media/$edir`; }
		}
		else
		{
			$mntgrep = "\"on /media/".$edir."\"";
			$mntout = trim(`mount -l | grep $mntgrep`);
			if($mntout == "")
			{
				$cmd = "";
				if($src->FSType == "smb")
				{
					$remotepath = $src->RemotePath;
					if(substr($remotepath, 0, 1) != "/") { $remotepath = "/".$remotepath; }
					$cmd = "mount -t cifs -o username=".$src->Username.",password=".$src->Password." //".$src->RemoteHost.$remotepath." /media/".$src->SourceCode;
					//echo "Need to run:\n";
					//echo "\t".$cmd."\n";
					`$cmd`;
				}
				else if($src->FSType == "sshfs")
				{
					$remotepath = $src->RemotePath;
					if(substr($remotepath, 0, 1) != "/") { $remotepath = "/".$remotepath; }
					$cmd = "su media -c \"sshfs ".$src->Username."@".$src->RemoteHost.":".$remotepath." -p".$src->Port." -o workaround=rename -o passwd_stdin /media/".$src->SourceCode."\"";
					`$cmd`;
				}
				else
				{
					echo "FSType: ".$src->FSType."\n";
				}
				//`umount /media/$edir`;
			}
			else
			{
				echo "mntout: ".$mntout."\n";
			}
		}
	}
}
closedir($dh);

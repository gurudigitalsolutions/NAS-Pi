<?//	Pi-NAS Module files modFiles
///////////////////////////////////////////////////////////////////////////////
//
//	Pi-NAS Files Module
//
///////////////////////////////////////////////////////////////////////////////
//
//	Copyright 2013, Brian Murphy
//	www.gurudigitalsolutions.com
//
///////////////////////////////////////////////////////////////////////////////

class modFiles extends PiNASModule
{
	public $Sources = array();

	public function Initialize()
	{
		$this->ModuleCode = "files";
		$this->MenuTitle = "Files";
		
		$this->AddSubMenu("sources", "Sources", true, array("admin", "filesource"));
		$this->AddSubMenu("browse", "Browse");
	}
	
	public function Render()
	{
		global $StyleSheets; $StyleSheets[] = "files";
		global $RequestVars;
		global $Scripts; $Scripts[] = "files";
		$toret = "";
		
		if($RequestVars["sub"] == "") { $RequestVars["sub"] = "browse"; }
		
		if(/*$RequestVars["sub"] == "" || */$RequestVars["sub"] == "sources")
		{
			$BlocksTemplate = file_get_contents(MODULEPATH."/files/templates/blocks.html");
			$BlocksEachTemplate = file_get_contents(MODULEPATH."/files/templates/blocks-each.html");
			$SourcesTemplate = file_get_contents(MODULEPATH."/files/templates/sources.html");
			$SourcesEachTemplate = file_get_contents(MODULEPATH."/files/templates/sources-each.html");
			$SourcesEnabledTemplate = file_get_contents(MODULEPATH."/files/templates/sources-enabled.html");
			$SourcesDisabledTemplate = file_get_contents(MODULEPATH."/files/templates/sources-disabled.html");
			$OutlineTemplate = file_get_contents(MODULEPATH."/files/templates/outline.html");
			$AddFormTemplate = file_get_contents(MODULEPATH."/files/templates/newsource.html");
			
			
			$this->LoadSources();
			$toret = $OutlineTemplate;
			$toret = str_replace("[SOURCES]", $this->BuildSourcesHTML($SourcesTemplate, $SourcesEachTemplate, $SourcesEnabledTemplate, $SourcesDisabledTemplate), $toret);
			$toret = str_replace("[ADDFORM]", $AddFormTemplate, $toret);
		}
		else if($RequestVars["sub"] == "moreinfo")
		{
			$SourceCode = $RequestVars["sourcecode"];
			if(file_exists(MODULEPATH."/files/sources/data/".$SourceCode))
			{
				$tsrc = unserialize(file_get_contents(MODULEPATH."/files/sources/data/".$SourceCode));
				$tsrc->InitFormElements();
				$props = get_object_vars($tsrc);
				
				$mitemplate = file_get_contents(MODULEPATH."/files/templates/sources-moreinfo.html");
				//$mieach = file_get_contents(MODULEPATH."/files/templates/sources-moreinfo-each.html");
				$mieach = file_get_contents(MODULEPATH."/files/templates/newsource-eachelement.html");
				
				$ttleach = "";
				foreach($tsrc->FormElements as $ekey=>$eval)
				{
					$teach = $mieach;
					$teach = str_replace("[FIELDTITLE]", $eval["fieldtitle"], $teach);
					$teach = str_replace("[FIELDTYPE]", $eval["fieldtype"], $teach);
					$teach = str_replace("[FIELDNAME]", $eval["fieldname"], $teach);
					//echo $eval["fieldmap"]."<br />\n";
					
					if(is_bool($tsrc->{$eval["fieldmap"]}))
					{
						if($tsrc->{$eval["fieldmap"]})
						{
							$teach = str_replace("[FIELDVALUE]", "enabled\" checked=\"checked", $teach);
						}
						else
						{
							$teach = str_replace("[FIELDVALUE]", "enabled", $teach);
						}
					}
					else
					{
						$teach = str_replace("[FIELDVALUE]", $tsrc->{$eval["fieldmap"]}, $teach);
					}
					
					$ttleach = $ttleach.$teach;
				}

				
				$mitemplate = str_replace("[EACHVAR]", $ttleach, $mitemplate);
				$mitemplate = str_replace("[SOURCETYPE]", $tsrc->FSType, $mitemplate);
				/*
				foreach($props as $ekey=>$eval)
				{
					if(!is_array($eval))
					{
						$teach = $mieach;
						if(strtolower($ekey) != "password")
						{
							$teach = str_replace("[KEY]", $ekey, $teach);
							$teach = str_replace("[VALUE]", $eval, $teach);
							
						}
						else
						{
							$teach = str_replace("[KEY]", $ekey, $teach);
							$teach = str_replace("[VALUE]", str_repeat("*", strlen($eval)), $teach);
							
						}
						$ttleach = $ttleach.$teach;
					}
					
				}
				$mitemplate = str_replace("[EACHVAR]", $ttleach, $mitemplate);
				*/
				echo $mitemplate;
				exit;
			}
			else
			{
				echo "Source not found.";
				exit;
			}
		}
		else if($RequestVars["sub"] == "newform")
		{
			echo $this->BuildNewSourceForm($RequestVars["sourcetype"]);
			
			
			exit;
		}
		else if($RequestVars["sub"] == "addsource")
		{
			$tmpsrc = "";
			
			$found = false;
			if($RequestVars["sourcecode"] != ""
			&& file_exists(MODULEPATH."/files/sources/data/".$RequestVars["sourcecode"]))
			{
				$tmpsrc = unserialize(file_get_contents(MODULEPATH."/files/sources/data/".$RequestVars["sourcecode"]));
				$found = true;
			}
			else
			{
				if($RequestVars["sourcetype"] == "smb")
				{
					$tmpsrc = new FileSourceSMB();
					$found = true;
				}
				else if($RequestVars["sourcetype"] == "local")
				{
					$tmpsrc = new FileSourceLocal();
					$found = true;
				}
				else if($RequestVars["sourcetype"] == "sshfs")
				{
					$tmpsrc = new FileSourceSSHFS();
					$found = true;
				}
				else if($RequestVars["sourcetype"] == "ftp")
				{
					$tmpsrc = new FileSourceFTP();
					$found = true;
				}
				else if($RequestVars["sourcetype"] == "bind")
				{
					$tmpsrc = new FileSourceBIND();
					$found = true;
				}
			}
			
			if($found == true)
			{
				if($tmpsrc->CreateFromForm())
				{
					$tmpsrc->Save();
					
					header("Location: /?module=files&sub=sources");
					exit;
				} else { error_log("Pi-Nas: Add File Source: Validation failed."); header("Location: /?module=files&sub=sources"); exit; }
			
			}
			else
			{
				error_log("Pi-Nas: Add File Source: No type found.");
				header("Location: /?module=files&sub=sources");
				exit;
			}
		}
		else if($RequestVars["sub"] == "deletesource")
		{
			if($RequestVars["sourcecode"] == "")
			{
				error_log("Pi-Nas: Delete file source: Source Code not provided");
				header("Location: /?module=files&sub=sources");
				exit;
			}
			
			if(!file_exists(MODULEPATH."/files/sources/data/".$RequestVars["sourcecode"]))
			{
				error_log("Pi-Nas: Delete file source: Source not found (".$RequestVars["sourcecode"].")");
				header("Location: /?module=files&sub=sources");
				exit;
			}
			
			$sourcecode = $RequestVars["sourcecode"];
			$source = unserialize(file_get_contents(MODULEPATH."/files/sources/data/".$sourcecode));
			$source->Delete();
			
			header("Location: /?module=files&sub=sources");
			exit;
		}
		else if($RequestVars["sub"] == "browse")
		{
			$dir = $RequestVars["dir"];
			if($dir == "") { $dir = "/"; }
			$dir = str_replace("..", ".", $dir);
			
			$Sources = array();
			$srclist = scandir(MODULEPATH."/files/sources/data");
			foreach($srclist as $es)
			{
				if($es != "." && $es != "..")
				{
					if(!is_dir(MODULEPATH."/files/sources/data/".$es))
					{
						$Sources[$es] = unserialize(file_get_contents(MODULEPATH."/files/sources/data/".$es));
					}
				}
			}
			
			$fullpath = "/media".$dir;
			$BrowseTemplate = file_get_contents(MODULEPATH."/files/templates/browse.html");
			$EachFileTemplate = file_get_contents(MODULEPATH."/files/templates/browse-eachfile.html");
			
			$SourceCode = $dir;
			if($SourceCode == "/") { $SourceCode = ""; }
			else
			{
				$SourceCode = substr($SourceCode, 1);
				
				if(strpos($SourceCode, "/") !== false)
				{
					$SourceCode = substr($SourceCode, 0, strpos($SourceCode, "/"));
				}
				
			}
			
			if(($dir != "/" && array_key_exists($SourceCode, $Sources))
			|| $dir == "/")
			{
				if(($SourceCode != "" && $Sources[$SourceCode]->HTTPShareEnabled)
				|| $SourceCode == "")
				{
					if(file_exists($fullpath))
					{
						if(is_dir($fullpath))
						{
							
							$fldrs = scandir($fullpath);
							$ttlfiles = "";
							
							foreach($fldrs as $efold)
							{
								if($efold != "."
								&& (($efold != ".." && $dir == "/") || $dir != "/"))
								{
									if(($dir == "/" && $Sources[$efold]->HTTPShareEnabled)
									|| ($dir != "/"))
									{
										$tfile = $EachFileTemplate;
										$pseudopath = str_replace("//", "/", $dir."/".$efold);
										
										if($efold == "..")
										{
											$pprts = explode("/", $dir);
											$pseudopath = "";
											for($ep = 0; $ep < count($pprts) - 1; $ep++) { $pseudopath = $pseudopath."/".$pprts[$ep]; }
											$pseudopath = str_replace("//", "/", $pseudopath);
										}
										
										
										$tfile = str_replace("[DIRPATH]", $pseudopath, $tfile);
										$tfile = str_replace("[FILENAME]", $efold, $tfile);
										$tfile = str_replace("[MODIFIED]", date("M d Y G:i:s", filemtime($fullpath."/".$efold)), $tfile);
										
										$mimetype = $this->MimeTypeOf($fullpath."/".$efold);
										
										$mtprts = explode("/", $mimetype);
										
										if(strtolower($mtprts[0]) == "video") { $tfile = str_replace("[ICONFILE]", "movie.gif", $tfile); }
										else if(strtolower($mtprts[0]) == "audio") { $tfile = str_replace("[ICONFILE]", "sound2.gif", $tfile); }
										else if(strtolower($mtprts[0]) == "archive") { $tfile = str_replace("[ICONFILE]", "compressed.gif", $tfile); }
										else if(strtolower($mtprts[0]) == "inode") { $tfile = str_replace("[ICONFILE]", "folder.gif", $tfile); }
										else { $tfile = str_replace("[ICONFILE]", "unknown.gif\" title=\"".$mtprts[0], $tfile); }
										
										$ttlfiles = $ttlfiles.$tfile;
									}
								}
							}
							
							$BrowseTemplate = str_replace("[PATH]", $dir, $BrowseTemplate);
							$toret = str_replace("[EACHFILE]", $ttlfiles, $BrowseTemplate);
						}
						else
						{
							//$mtcmd = "file \"".$fullpath."\"";
							//$mimetype = trim(`$mtcmd`);
							$mimetype = $this->MimeTypeOf($fullpath);
							$filename = substr($fullpath, strrpos($fullpath, "/") + 1);
							
							header("Content-Type: ".$mimetype);
							header("Expires: 0");
							header("Content-Disposition: attachment; filename=".$filename);
							header("Content-Length: ".filesize($fullpath));
							readfile($fullpath);
							exit;
						}
					}
					else
					{
						$toret = "Path (".$dir.") not found.";
					}
				} //	httpshareenabled
				else
				{
					$toret = "That share is not enabled.";
				}
			} //	if array_key_exists
			else
			{
				$toret = "You are not authorized to view that share.";
			}
		}
		
		/*$blocks = $this->GetBlocks();
		if(count($blocks) > 0)
		{
			$ttlblocks = "";
			foreach($blocks as $eblock)
			{
				$tbtmp = $BlocksEachTemplate;
				$tbtmp = str_replace("[DEVICE]", $eblock["device"], $tbtmp);
				$tbtmp = str_replace("[LABEL]", $eblock["label"], $tbtmp);
				$tbtmp = str_replace("[UUID]", $eblock["uuid"], $tbtmp);
				$tbtmp = str_replace("[TYPE]", $eblock["type"], $tbtmp);
				
				$ttlblocks = $ttlblocks.$tbtmp;
			}
			
			$BlocksTemplate = str_replace("[EACHBLOCK]", $ttlblocks, $BlocksTemplate);
		}
		else { $BlocksTemplate = "No block devices."; }
		
		$toret = $toret.$BlocksTemplate;*/
		
		/*$berry = new FileSourceLocal();
		$berry->Title = "berry";
		$berry->SourceCode = "berry";
		$berry->Enabled = true;
		$berry->UUID  = "3a4ec15e-d8df-4b42-83b7-d2dd66680585";
		$berry->Label = "berry";
		$berry->FSType = "ext2";
		$berry->FindBy = "uuid";
		file_put_contents(MODULEPATH."/files/sources/data/".$berry->SourceCode, serialize($berry));
		*/
		return $toret;
	}
	
	public function GetBlocks()
	{
		$blkcmd = `/sbin/blkid`;

		$parts = explode("\n", trim($blkcmd));
		$toret = array();
		
		foreach($parts as $epart)
		{
			if(substr($epart, 0, 12) != "/dev/mmcblk0")
			{
				//	We aren't going to list the SD card as a device for this
				$tblock = array();
				
				$devpts = explode(" ", $epart);
				$devpts[0] = str_replace(":", "", $devpts[0]);
				
				$tblock["device"] = $devpts[0];
				
				for($ebp = 1; $ebp < count($devpts); $ebp++)
				{
					$kvprts = explode("=", $devpts[$ebp]);
					
					$tblock[strtolower($kvprts[0])] = str_replace("\"", "", $kvprts[1]);
					
				}
				
				$toret[] = $tblock;
			}
		}
		
		
		return $toret;
	}
	
	public function LoadSources()
	{
		$dh = opendir(MODULEPATH."/files/sources/data");
		
		while(false !== ($edir = readdir($dh)))
		{
			if($edir != "." && $edir != "..")
			{
				$this->Sources[$edir] = unserialize(file_get_contents(MODULEPATH."/files/sources/data/".$edir));
			}
		}
		
		closedir($dh);
		
		return true;
	}
	
	public function BuildSourcesHTML($sourcestemplate, $eachsourcetemplate, $enabledtemplate, $disabledtemplate)
	{
		$allsources = "";
		foreach(array_keys($this->Sources) as $ekey)
		{
			$allsources = $allsources.$this->BuildSourceHTML($ekey, $eachsourcetemplate, $enabledtemplate, $disabledtemplate);
		}
		
		$sourcestemplate = str_replace("[EACHSOURCE]", $allsources, $sourcestemplate);
		return $sourcestemplate;
	}
	
	public function BuildSourceHTML($sourcecode, $eachsourcetemplate, $enabledtemplate, $disabledtemplate)
	{
		$tt = $eachsourcetemplate;
		$tt = str_replace("[SOURCECODE]", $this->Sources[$sourcecode]->SourceCode, $tt);
		$tt = str_replace("[TITLE]", $this->Sources[$sourcecode]->Title, $tt);
		$tt = str_replace("[TYPE]", $this->Sources[$sourcecode]->FSType, $tt);
		
		if($this->Sources[$sourcecode]->HTTPShareEnabled)
		{
			$tt = str_replace("[SHARED]", "Yes", $tt);
		}
		else
		{
			$tt = str_replace("[SHARED]", "No", $tt);
		}
		
		if($this->Sources[$sourcecode]->Enabled)
		{
			$tt = str_replace("[ENABLED]", $enabledtemplate, $tt);
		}
		else
		{
			$tt = str_replace("[ENABLED]", $disabledtemplate, $tt);
		}
		
		return $tt;
	}
	
	public function BuildNewSourceForm($sourcetype)
	{
		$formtemplate = file_get_contents(MODULEPATH."/files/templates/newsource-form.html");
		$eachrow = file_get_contents(MODULEPATH."/files/templates/newsource-eachelement.html");
		
		$so = "";
		if($sourcetype == "smb") { $so = new FileSourceSMB(); }
		else if($sourcetype == "local") { $so = new FileSourceLocal(); }
		else if($sourcetype == "sshfs") { $so = new FileSourceSSHFS(); }
		else if($sourcetype == "ftp") { $so = new FileSourceFTP(); }
		else if($sourcetype == "bind") { $so = new FileSourceBIND(); }
		else { return "Unknown source type."; }
		
		$ttlrows = "";
		$so->InitFormElements();
		
		foreach($so->FormElements as $eel)
		{
			$trow = $eachrow;
			$trow = str_replace("[FIELDNAME]", $eel["fieldname"], $trow);
			$trow = str_replace("[FIELDTYPE]", $eel["fieldtype"], $trow);
			$trow = str_replace("[FIELDTITLE]", $eel["fieldtitle"], $trow);
			$trow = str_replace("[FIELDVALUE]", "", $trow);
			
			$ttlrows = $ttlrows.$trow;
		}
		
		$formtemplate = str_replace("[SOURCETYPE]", $sourcetype, $formtemplate);
		$formtemplate = str_replace("[EACHELEMENT]", $ttlrows, $formtemplate);
		return $formtemplate;
	}
	
	public function MimeTypeOf($fullpath)
	{
		$mtcmd = "file -bi \"".$fullpath."\"";
		return trim(`$mtcmd`);
	}
}

?>

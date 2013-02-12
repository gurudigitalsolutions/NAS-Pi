$panes = Array();
var paneTemplates = Array();
var paneTemplateIDs = Array();
var windowWidth = 800;
var windowHeight = 600;

function browserPane(id)
{
	this.id = id;
	this.dir = "/";
	this.nodes = Array();
	this.element = "files_browse_filelist_right";
	
	this.LoadNodes = function()
	{
		if (window.XMLHttpRequest)
		{// code for IE7+, Firefox, Chrome, Opera, Safari
			lnxhr = new XMLHttpRequest();
		}
		else
		{// code for IE6, IE5
			lnxhr = new ActiveXObject("Microsoft.XMLHTTP");
		}

		lnxhr.open("GET", "/?module=files&sub=pane&dir="+this.dir, false);
		lnxhr.send(null);


		//document.getElementById(this.element).innerHTML = lnxhr.responseText;
		this.nodes = eval(lnxhr.responseText);
		this.Render();
		//document.getElementById(this.element).innerHTML = this.nodes[0].name;
		//alert(lnxhr.responseText);
	}
	
	this.Render = function()
	{
		var fullout = "";
		for(var en = 0; en < this.nodes.length; en++)
		{
			var tnodetm = Template("browse-eachfile");
			while(tnodetm.indexOf("[FILENAME]") !== -1) { tnodetm = tnodetm.replace("[FILENAME]", this.nodes[en].name); }
			while(tnodetm.indexOf("[DIRPATH]") !== -1) { tnodetm = tnodetm.replace("[DIRPATH]", this.dir); }
			while(tnodetm.indexOf("[PANEID]") !== -1) { tnodetm = tnodetm.replace("[PANEID]", this.id); }
			while(tnodetm.indexOf("[NODEID]") !== -1) { tnodetm = tnodetm.replace("[NODEID]", en); }
			
			if(this.nodes[en].icon == "audio") { tnodetm = tnodetm.replace("[ICON]", "sound2"); }
			else if(this.nodes[en].icon == "video") { tnodetm = tnodetm.replace("[ICON]", "movie"); }
			else if(this.nodes[en].icon == "archive") { tnodetm = tnodetm.replace("[ICON]", "compressed"); }
			else if(this.nodes[en].icon == "inode") { tnodetm = tnodetm.replace("[ICON]", "folder"); }
			else { tnodetm = tnodetm.replace("[ICON]", "unknown"); }
			
			fullout = fullout + tnodetm;
			
			
		}
		
		if(this.dir != "/")
		{
			var tnodetm = Template("browse-eachfile");
			while(tnodetm.indexOf("[FILENAME]") !== -1) { tnodetm = tnodetm.replace("[FILENAME]", ".."); }
			while(tnodetm.indexOf("[DIRPATH]") !== -1) { tnodetm = tnodetm.replace("[DIRPATH]", this.ParentDir(this.dir)); }
			while(tnodetm.indexOf("[PANEID]") !== -1) { tnodetm = tnodetm.replace("[PANEID]", this.id); }
			while(tnodetm.indexOf("[NODEID]") !== -1) { tnodetm = tnodetm.replace("[NODEID]", "-1"); }
			tnodetm = tnodetm.replace("[ICON]", "folder");
			
			fullout = tnodetm + fullout;
		}
		
		fullout = this.dir+"<br />"+fullout;
		
		var outertm = Template("browse-pane");
		outertm = outertm.replace("[EACHFILE]", fullout);
		document.getElementById(this.element).innerHTML = outertm;
		
		for(var en = 0; en < this.nodes.length; en++)
		{
			var nodey = document.getElementById('files_each_'+this.id+"_"+en);
			
			if(document.addEventListener) {
				nodey.addEventListener('contextmenu', function(e) {
					RenderRightClickMenu(e, this.id);
					//alert("Right clicked in pane "+this.id);
					e.preventDefault();
				}, false);
			} else {
				nodey.attachEvent('oncontextmenu', function() {
					RenderRightClickMenu(e, this.id);
					window.event.returnValue = false;
				});
			}
		}
	}
	
	
	
	this.Mirror = function(nodeid)
	{
		var opaneid = -1;
		if(this.id == 0) { opaneid = 1; } else { opaneid = 0; }
		$panes[opaneid].dir = this.dir+"/"+this.nodes[nodeid].name;
		
		//Navigate(opaneid, nodeid);
		$panes[opaneid].LoadNodes();
	}
	
	this.ClickFile = function(nodeid)
	{
		if(this.nodes[nodeid].selected)
		{
			this.nodes[nodeid].selected = false;
			
			var noderow = document.getElementById("files_each_"+this.id+"_"+nodeid);
			noderow.style.background = "#E0E0E0";
		}
		else
		{
			this.nodes[nodeid].selected = true;
			
			var noderow = document.getElementById("files_each_"+this.id+"_"+nodeid);
			noderow.style.background = "#C0C0FF";
		}
	}
	
	this.MouseOverNode = function(nodeid)
	{
		if(this.nodes[nodeid].selected)
		{
			var noderow = document.getElementById("files_each_"+this.id+"_"+nodeid);
			noderow.style.background = "#A0A0FF";
		}
		else
		{
			var noderow = document.getElementById("files_each_"+this.id+"_"+nodeid);
			noderow.style.background = "#D0D0D0";
		}
	}
	
	this.MouseOutNode = function(nodeid)
	{
		if(this.nodes[nodeid].selected)
		{
			var noderow = document.getElementById("files_each_"+this.id+"_"+nodeid);
			noderow.style.background = "#C0C0FF";
		}
		else
		{
			var noderow = document.getElementById("files_each_"+this.id+"_"+nodeid);
			noderow.style.background = "#E0E0E0";
		}
	}
	
	this.ParentDir = function(dirname)
	{
		return dirname.substring(0, dirname.lastIndexOf("/"));
	}
	
	this.UploadFiles = function()
	{
		var allnodes = "";
		for(var en = 0; en < this.nodes.length; en++)
		{
			if(this.nodes[en].selected) { allnodes = allnodes + this.nodes[en].name + "\n"; }
		}
		
		allnodes = "Selected Nodes:\n\n" + allnodes;
		alert(allnodes);
	}
}

function RenderRightClickMenu(e, rowid)
{
	var paneid = rowid.replace("files_each_", "");
	paneid = paneid.substring(0, paneid.indexOf("_"));
	var nodeid = rowid.substring(rowid.lastIndexOf("_"));
	nodeid = nodeid.replace("_", "");
	
	//alert("Right click menu on pane "+paneid+" node "+nodeid);
	
	var menu = document.getElementById('files_clickmenu');
	menu.style.display = "block";
	
	var mx = e.clientX;
	var my = e.clientY;
	
	menu.style.position = "fixed";
	menu.style.top = (my - 5)+"px";
	menu.style.left = (mx - 5)+"px";
	menu.style.background = "#C0C0C0";
	menu.style.borderWidth = "1px";
	menu.style.borderStyle = "solid";
	
	var mentmp = Template("browse-rightclick");
	
	while(mentmp.indexOf("[NODEID]") !== -1) { mentmp = mentmp.replace("[NODEID]", nodeid); }
	while(mentmp.indexOf("[PANEID]") !== -1) { mentmp = mentmp.replace("[PANEID]", paneid); }
	
	
	menu.innerHTML = mentmp;
}

function Navigate(pane, nodeid)
{
	if($panes[pane].dir == "/") { $panes[pane].dir = "/"+ $panes[pane].nodes[nodeid].name; }
	else
	{
		if(nodeid == -1)
		{
			//alert($panes[pane].dir+"\n\n"+$panes[pane].ParentDir($panes[pane].dir));
			$panes[pane].dir = $panes[pane].ParentDir($panes[pane].dir);
			if($panes[pane].dir == "") { $panes[pane].dir = "/"; }
		}
		else
		{
			
			$panes[pane].dir = $panes[pane].dir+"/"+$panes[pane].nodes[nodeid].name;
		}
	}
	
	if(nodeid != -1 && $panes[pane].nodes[nodeid].isfile == true)
	{
		document.location = "/?module=files&sub=pane&dir="+$panes[pane].dir;
		$panes[pane].dir = $panes[pane].ParentDir($dir);
	}
	else
	{
		$panes[pane].LoadNodes();
	}
}

function MouseOverMenu(mn)
{
	mn.style.background = "#D0D0FF";
}

function MouseOutMenu(mn)
{
	mn.style.background = "#C0C0C0";
}

function HideMenu()
{
	var menu = document.getElementById("files_clickmenu");
	menu.style.display = "none";
	menu.innerHTML = "";
}

function paneNode(id)
{
	this.id = id;
	this.name = "";
	this.selected = false;
	this.isfile = false;
}

function InitializeBrowser()
{
	$panes[0] = new browserPane(0);
	$panes[0].element = "files_browse_pane0";
	$panes[1] = new browserPane(1);
	$panes[1].element = "files_browse_pane1";
	
	LoadTemplate("browse-pane");
	LoadTemplate("browse-eachfile");
	LoadTemplate("browse-rightclick");
	
	if(window.innerWidth) {
		windowWidth = window.innerWidth;
		windowHeight = window.innerHeight;
	} else if(document.documentElement.clientWidth) {
		windowWidth = document.documentElement.clientWidth;
		windowHeight = document.documentElement.clientHeight;
	} else {
		windowWidth = document.getElementsByTagName('body')[0].clientWidth;
		windowHeight = document.getElementsByTagName('body')[0].clientHeight;
	}
	
	$panes[0].LoadNodes();
	$panes[1].LoadNodes();
	
	document.addEventListener("click", HideMenu, false);
	
	var po = document.getElementById($panes[0].element);
	var pt = document.getElementById($panes[1].element);
	var eachwidth = windowWidth - 300;
	eachwidth = eachwidth / 2;
	eachwidth = eachwidth - 5;
	
	po.style.width = eachwidth+"px";
	pt.style.width = eachwidth+"px";
	
	var eachheight = windowHeight - 50 - 30;
	po.style.height = eachheight+"px";
	pt.style.height = eachheight+"px";
	
	po.style.overflow = "scroll";
	pt.style.overflow = "scroll";
	
	var filebuttons = document.getElementById("files_browse_buttons");
	filebuttons.style.height = eachheight+"px";
}

function LoadTemplate(template)
{
	if (window.XMLHttpRequest)
	{// code for IE7+, Firefox, Chrome, Opera, Safari
		lnxhr = new XMLHttpRequest();
	}
	else
	{// code for IE6, IE5
		lnxhr = new ActiveXObject("Microsoft.XMLHTTP");
	}

	lnxhr.open("GET", "/?module=files&sub=panetemplate&template="+template, false);
	lnxhr.send(null);
	
	var ptid = paneTemplateIDs.length;
	paneTemplateIDs[ptid] = template;
	paneTemplates[ptid] = lnxhr.responseText;

}

function Template(template)
{
	for(var et = 0; et < paneTemplateIDs.length; et++)
	{
		if(paneTemplateIDs[et] == template) { return paneTemplates[et]; }
	}
	
	return "";
}

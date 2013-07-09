var InstalledAddons = Array();
var AvailableAddons = Array();
var Browser = null;

function AddOnMoreInfo(modcode)
{
	var aodiv = document.getElementById("addons_moreinfo_"+modcode);
	var aolnk = document.getElementById("addons_moreinfo_link_"+modcode);
	var cdisp = aodiv.style.display;
	
	if(cdisp == "block")
	{
		aodiv.style.display = "none";
		aolnk.innerHTML = "[More Info]";
	}
	else 
	{
		aodiv.style.display = "block";
		aolnk.innerHTML = "[Less Info]";
	}
}

function InitPackageBrowser()
{
	if (window.XMLHttpRequest)
	{// code for IE7+, Firefox, Chrome, Opera, Safari
		lnxhr = new XMLHttpRequest();
	}
	else
	{// code for IE6, IE5
		lnxhr = new ActiveXObject("Microsoft.XMLHTTP");
	}

	lnxhr.open("GET", "/?module=addons&sub=installedaddons", false);
	lnxhr.send(null);

	InstalledAddons = eval("("+lnxhr.responseText+")");
	
	if (window.XMLHttpRequest)
	{// code for IE7+, Firefox, Chrome, Opera, Safari
		lnxhr = new XMLHttpRequest();
	}
	else
	{// code for IE6, IE5
		lnxhr = new ActiveXObject("Microsoft.XMLHTTP");
	}

	lnxhr.open("GET", "/?module=addons&sub=availableaddons", false);
	lnxhr.send(null);

	AvailableAddons = eval("("+lnxhr.responseText+")");
	//document.getElementById(this.element).innerHTML = lnxhr.responseText;
	
	Browser = new AddOnBrowser();
	
	Browser.tableTemplate = Browser.LoadTemplate("table");
	Browser.tableRowTemplate = Browser.LoadTemplate("tablerow");
	Browser.zoomTemplate = Browser.LoadTemplate("zoom");
	Browser.screenshotTemplate = Browser.LoadTemplate("screenshot");
	Browser.installlinkTemplate = Browser.LoadTemplate("installlink");
	Browser.uninstalllinkTemplate = Browser.LoadTemplate("uninstalllink");
	
	Browser.QueryActiveInstalls();
	
	Browser.Render();
}

function AddOnBrowser()
{
	this.RepoHost = "http://10.42.0.151:3000";
	this.tableTemplate = "";
	this.tableRowTemplate = "";
	this.zoomTemplate = "";
	this.screenshotTemplate = "";
	this.installlinkTemplate = "";
	this.uninstalllinkTemplate = "";
	
	this.PermanentAddOns = Array("addons", "users", "admin", "files");
	this.ActiveInstalls = Array();
	
	this.Render = function()
	{
		var toret = "";
		var allrows = "";
		for(var i = 0; i < AvailableAddons.length; i++)
		{
			var thisrow = this.tableRowTemplate;
			thisrow = thisrow.replace("[TITLE]", AvailableAddons[i].title);
			thisrow = thisrow.replace("[AUTHOR]", AvailableAddons[i].displayname);
			thisrow = thisrow.replace("[MODCODE]", AvailableAddons[i].modcode);
			thisrow = thisrow.replace("[VERSION]", AvailableAddons[i].version);
			thisrow = thisrow.replace("[ICONURL]", AvailableAddons[i].modcode);
			
			if(this.IsAddOnInstalled(AvailableAddons[i].modcode))
			{
				AvailableAddons[i].installed = true;
				thisrow = thisrow.replace("[ROWSTYLE]", "addons_row_installed");
			}
			else
			{
				AvailableAddons[i].installed = false;
				thisrow = thisrow.replace("[ROWSTYLE]", "addons_row_uninstalled");
			}
			
			if(this.IsAddOnPermanent(AvailableAddons[i].modcode))
			{
				//	You cannot uninstall the permanent add-ons.
				thisrow = thisrow.replace("[OPTIONS]", "");
			}
			else
			{
				//	You *can* install and uninstall other add-ons
				if(AvailableAddons[i].installed)
				{
					//	Display Uninstall link
					var tlink = this.uninstalllinkTemplate;
					tlink = tlink.replace("[MODCODE]", AvailableAddons[i].modcode);
					thisrow = thisrow.replace("[OPTIONS]", tlink);
				}
				else
				{
					//	Display install link
					var tlink = this.installlinkTemplate;
					tlink = tlink.replace("[MODCODE]", AvailableAddons[i].modcode);
					thisrow = thisrow.replace("[OPTIONS]", tlink);
				}
			}
			
			allrows = allrows+thisrow
		}
		
		
		toret = this.tableTemplate.replace("[EACHROW]", allrows);
		
		
		
		document.getElementById("addons_content").innerHTML = toret;
	}
	
	this.LoadTemplate = function(tempname)
	{
		if (window.XMLHttpRequest)
		{// code for IE7+, Firefox, Chrome, Opera, Safari
			lnxhr = new XMLHttpRequest();
		}
		else
		{// code for IE6, IE5
			lnxhr = new ActiveXObject("Microsoft.XMLHTTP");
		}

		lnxhr.open("GET", "/?module=addons&sub=gettemplate&template="+tempname, false);
		lnxhr.send(null);

		return lnxhr.responseText;
	}
	
	this.IsAddOnInstalled = function(modcode)
	{
		for(var x in InstalledAddons)
		{
			if(x == modcode) { return true; }
		}
		
		return false;
	}
	
	this.idFromModCode = function(modcode)
	{
		for(var i = 0; i < AvailableAddons.length; i++)
		{
			if(AvailableAddons[i].modcode == modcode) { return i; }
		}
		
		return 0;
	}
	
	this.ZoomIn = function(modcode)
	{
		var cont = document.getElementById("addons_content");
		var toret = this.zoomTemplate;
		var id = this.idFromModCode(modcode);
		
		toret = toret.replace("[AUTHOR]", AvailableAddons[id].displayname);
		toret = toret.replace("[TITLE]", AvailableAddons[id].title);
		toret = toret.replace("[ICONURL]", AvailableAddons[id].modcode);
		toret = toret.replace("[DESCRIPTION]", AvailableAddons[id].description);
		toret = toret.replace("[VERSION]", AvailableAddons[id].version);
		toret = toret.replace("[TTLDOWNLOADS]", AvailableAddons[id].ttldownloads);
		
		var sshtml = "";
		for(var ess = 0; ess < AvailableAddons[id].screenshots.length; ess++)
		{
			var tscr = this.screenshotTemplate;
			while(tscr.indexOf("[SCREENSHOTURL]") != -1) { tscr = tscr.replace("[SCREENSHOTURL]", this.RepoHost + AvailableAddons[id].screenshots[ess]); }
			sshtml = sshtml + tscr;
		}
		toret = toret.replace("[SCREENSHOTS]", sshtml);
		cont.innerHTML = toret;
	}
	
	this.ZoomOut = function()
	{
		this.Render();
	}
	
	this.IsAddOnPermanent = function(modcode)
	{
		for(var ea = 0; ea < this.PermanentAddOns.length; ea++)
		{
			if(this.PermanentAddOns[ea] == modcode) { return true; }
		}
		
		return false;
	}
	
	this.QueryActiveInstalls()
	{
		if (window.XMLHttpRequest)
		{// code for IE7+, Firefox, Chrome, Opera, Safari
			lnxhr = new XMLHttpRequest();
		}
		else
		{// code for IE6, IE5
			lnxhr = new ActiveXObject("Microsoft.XMLHTTP");
		}

		lnxhr.open("GET", "/?module=addons&sub=listinstalling, false);
		lnxhr.send(null);

		this.ActiveInstalls = eval("("+lnxhr.responseText+")");
	}
}

function AddOn()
{
	this.title = "";
	this.author = "";
	this.authorusername = "";
	this.modcode = "";
	this.version = "";
	this.versionint = 0;
	this.id = 0;
	this.description = "";
	this.shortdesc = "";
	
}

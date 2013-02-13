var UpdateTranDelayTime = 10000;
var kaktive = 0;
var windowWidth = 800;
var windowHeight = 600;
var btTabs = Array();
var btActiveTab = 0;

var btTemplates = Array();
var btTemplateIDs = Array();
//var tccount = 0;

function btTab(ID, Title, Engine)
{
	this.id = ID;
	this.title = Title;
	this.element = "";
	this.engine = Engine;
	
	var _this = this;
	
	this.Render = function()
	{
		var fullh = this.engine.Render();
		this.element = this.getElement();
		this.element.innerHTML = fullh;
	}
	
	this.getElement = function()
	{
		return document.getElementById("btguru_content");
	}
	
	this.Update = function()
	{
		this.engine.Update();
		if(btActiveTab == this.id) { this.Render(); }
	}
}

function btTransmissionEngine()
{
	this.torrents = Array();
	this.initialized = false;
	
	this.Initialize = function()
	{
		this.UpdateStats();
		this.initialized = true;
	}
	
	this.Render = function()
	{
		if(this.initialized == false) { this.Initialize(); }
		var fullout = "";
		var tmlatey = Template("trans-current");
		
		if(this.torrents.length == 0) { return ""; }
		
		
		
		for(var et = 0; et < this.torrents.length; et++)
		{
			var ttrp = tmlatey;
			while(ttrp.indexOf("[NAME]") !== -1) { ttrp = ttrp.replace("[NAME]", this.torrents[et].name); }
			while(ttrp.indexOf("[PROGRESSPCT]") !== -1) { ttrp = ttrp.replace("[PROGRESSPCT]", this.torrents[et].progress); }
			while(ttrp.indexOf("[STATS]") !== -1) { ttrp = ttrp.replace("[STATS]", this.torrents[et].stats); }
			while(ttrp.indexOf("[DOWNLOADRATE]") !== -1) { ttrp = ttrp.replace("[DOWNLOADRATE]", this.torrents[et].downloadrate); }
			while(ttrp.indexOf("[UPLOADRATE]") !== -1) { ttrp = ttrp.replace("[UPLOADRATE]", this.torrents[et].uploadrate); }
			while(ttrp.indexOf("[PROGRESSWIDTH]") !== -1) { ttrp = ttrp.replace("[PROGRESSWIDTH]", (windowWidth - 250)); }
			
			fullout = fullout + ttrp;
		}
		
		
		return fullout;
	}
	
	this.UpdateStats = function()
	{
		if (window.XMLHttpRequest)
		{// code for IE7+, Firefox, Chrome, Opera, Safari
			lnxhr = new XMLHttpRequest();
		}
		else
		{// code for IE6, IE5
			lnxhr = new ActiveXObject("Microsoft.XMLHTTP");
		}

		lnxhr.open("GET", "/?module=btguru&sub=transmissionstate&js=1", false);
		lnxhr.send(null);
		
		this.torrents = eval(lnxhr.responseText);
		
		var _this = this;
		setTimeout(function() { _this.UpdateStats(); }, UpdateTranDelayTime);
	}
	
	this.Update = function()
	{
		this.UpdateStats();
	}
}

function btSearchEngine()
{
	this.results = Array();
	
	this.Initialize = function()
	{
		
	}
	
	this.Render = function()
	{
		if(this.results.length == 0) { return "No results."; }
		var searchtmp = Template("search-outline");
		var ttlresults = "";
		
		for(var er = 0; er < this.results.length; er++)
		{
			var tres = Template("search-each");
			var resact = "";
			
			while(tres.indexOf("[RESULTID]") !== -1) { tres = tres.replace("[RESULTID]", er); }
			while(tres.indexOf("[SIZE]") !== -1) { tres = tres.replace("[SIZE]", this.results[er].size); }
			while(tres.indexOf("[DATE]") !== -1) { tres = tres.replace("[DATE]", this.results[er].date); }
			while(tres.indexOf("[SOURCECOUNT]") !== -1) { tres = tres.replace("[SOURCECOUNT]", this.results[er].sourcecount); }
			while(tres.indexOf("[SEEDCOUNT]") !== -1) { tres = tres.replace("[SEEDCOUNT]", this.results[er].seedcount); }
			while(tres.indexOf("[LEECHCOUNT]") !== -1) { tres = tres.replace("[LEECHCOUNT]", this.results[er].leechcount); }
			while(tres.indexOf("[NAME]") !== -1) { tres = tres.replace("[NAME]", this.results[er].name); }
			while(tres.indexOf("[TORRENTURL]") !== -1) { tres = tres.replace("[TORRENTURL]", this.results[er].torrenturl); }
			
			if(this.results[er].torrentpresent == 1) { resact = Template("result-action-none"); }
			else { resact = Template("result-action-add"); }
			
			while(resact.indexOf("[NAME]") !== -1) { resact = resact.replace("[NAME]", this.results[er].name); }
			while(resact.indexOf("[TORRENTURL]") !== -1) { resact = resact.replace("[TORRENTURL]", this.results[er].torrenturl); }
			while(resact.indexOf("[RESULTID]") !== -1) { resact = resact.replace("[RESULTID]", er); }
			while(tres.indexOf("[RESULTACTION]") !== -1) { tres = tres.replace("[RESULTACTION]", resact); }
			
			if(this.results[er].torrentpresent == 1)
			{ while(tres.indexOf("[RESULTCLASS]") !== -1) { tres = tres.replace("[RESULTCLASS]", "have"); } }
			else { while(tres.indexOf("[RESULTCLASS]") !== -1) { tres = tres.replace("[RESULTCLASS]", "new"); }}
			ttlresults = ttlresults + tres;
		}
		
		searchtmp = searchtmp.replace("[RESULTROWS]", ttlresults);
		return searchtmp;
	}
	
	this.Search = function()
	{
		var sbid = document.getElementById('query');
		var searchquery = sbid.value;
		
		if (window.XMLHttpRequest)
		{// code for IE7+, Firefox, Chrome, Opera, Safari
			lfnxhr = new XMLHttpRequest();
		}
		else
		{// code for IE6, IE5
			lfnxhr = new ActiveXObject("Microsoft.XMLHTTP");
		}

		lfnxhr.open("GET", "/?module=btguru&sub=search&query="+searchquery+"&js=1", false);
		lfnxhr.send(null);
		
		this.results = eval(lfnxhr.responseText);
	}
	
	this.Update = function()
	{
		//	This doesn't update :)
	}
}

function DoSearch()
{
	btActiveTab = 1;
	btTabs[1].engine.Search();
	RenderTabBar();
	btTabs[1].Render();
}

function EnterHandler(e)
{
	if(e.keyCode == 13)
	{
		btActiveTab = 1;
		btTabs[1].engine.Search();
		
		RenderTabBar();
		btTabs[1].Render();
	}
}

function LoadPage(url, output)
{
	if(kaktive == 1)
	{
		setTimeout("LoadPage('"+url+"', '"+output+"')", 1200);
		//alert("LoadPage('"+url+"', '"+output+"')");
		return;
	}

	kaktive = 1;

	if (window.XMLHttpRequest)
	{// code for IE7+, Firefox, Chrome, Opera, Safari
		xhaddtobuds=new XMLHttpRequest();
	}
	else
	{// code for IE6, IE5
		xhaddtobuds=new ActiveXObject("Microsoft.XMLHTTP");
	}

	xhaddtobuds.open("GET", url, false);
	xhaddtobuds.send(null);

	if(output == null)
	{
		//	Function was directed to not display the output anywhere.
	}
	else
	{
		document.getElementById(output).innerHTML = xhaddtobuds.responseText;
	}
	
	kaktive = 0;
}

function LoadPageCallFunction(url, funcname, args)
{
	if(kaktive == 1)
	{
		setTimeout("LoadPageCallFunction('"+url+"', '"+funcname+"', '"+args+"')", 1200);
		return;
	}

	kaktive = 1;

	if (window.XMLHttpRequest)
	{// code for IE7+, Firefox, Chrome, Opera, Safari
		xhaddtobuds=new XMLHttpRequest();
	}
	else
	{// code for IE6, IE5
		xhaddtobuds=new ActiveXObject("Microsoft.XMLHTTP");
	}

	xhaddtobuds.open("GET", url, false);
	xhaddtobuds.send(null);

	
	kaktive = 0;
	var fn = window[funcname];
	fn(xhaddtobuds.responseText, args);

}

function AddTorrent(torrentlink, resultid)
{
	//var rsadd = document.getElementById('result_addtorrent_'+resultid);
	var resultrow = document.getElementById('result_row_'+resultid);
	//rsadd.innerHTML = "...";
	
	resultrow.style.borderColor = "#12C672";
	var encurl = encodeURIComponent(torrentlink);
	//alert("Encoded URL: "+encurl);
	LoadPageCallFunction("/?module=btguru&sub=addtorrent&torrentlink="+encurl, 'AddTorrentResponse', Array(resultid));
}

function AddTorrentResponse(restext, args)
{
	if(restext.substring(0, 4) == "YEAH")
	{
		
		var torresdiv = document.getElementById("result_row_"+args[0]);
		torresdiv.style.background = '#80F080';
		//(document.getElementById('result_addtorrent_'+args[0])).innerHTML = "&lt;&lt;";
	}
	else if(restext.substring(0, 4) == "FAIL")
	{
		alert("Adding torrent failed:\n"+restext.substring(5));
		var torresdiv = document.getElementById("result_row_"+args[0]);
		torresdiv.style.borderColor = "#CC3310";
		//(document.getElementById('result_addtorrent_'+args[0])).innerHTML = "---";
	}
}


function RenderTabBar()
{
	var fullbar = "";
	for(var et = 0; et < btTabs.length; et++)
	{
		var ttab = Template("tabs-each");
		
		while(ttab.indexOf("[TABID]") !== -1) { ttab = ttab.replace("[TABID]", et); }
		while(ttab.indexOf("[TITLE]") !== -1) { ttab = ttab.replace("[TITLE]", btTabs[et].title); }
		
		fullbar = fullbar + ttab;
	}
	
	fullbar = fullbar + "<div class=\"btguru_extratabs\">&nbsp;</div>";
	
	var dabar = document.getElementById("btguru_tabs");
	dabar.innerHTML = fullbar;
	
	for(var et = 0; et < btTabs.length; et++)
	{
		var ttcss = document.getElementById("btguru_tab_"+et);
		
		if(et == btActiveTab)
		{
			ttcss.style.borderStyle = "solid";
			ttcss.style.borderLeftWidth = "2px";
			ttcss.style.borderRightWidth = "2px";
			ttcss.style.borderTopWidth = "2px";
			ttcss.style.borderBottomWidth = "2px";
			ttcss.style.borderBottomColor = "#FFFFFF";
			ttcss.style.background = "#FFFFFF";
		}
		else
		{
			ttcss.style.borderStyle = "solid";
			ttcss.style.borderLeftWidth = "2px";
			ttcss.style.borderRightWidth = "2px";
			ttcss.style.borderTopWidth = "2px";
			ttcss.style.borderBottomWidth = "2px";
			ttcss.style.background = "#9A9A9A";
		}
	}
}

function MouseOverTab(id)
{
	var tb = document.getElementById("btguru_tab_"+id);
	tb.style.background = "#C0C0C0";
}

function MouseOutTab(id)
{
	
	var tb = document.getElementById("btguru_tab_"+id);
	
	if(id == btActiveTab) { tb.style.background = "#FFFFFF"; }
	else { tb.style.background = "#9A9A9A"; }
}

function ClickTab(id)
{
	btActiveTab = id;
	RenderTabBar();
	btTabs[id].Render();
}

function InitializebtGuru()
{
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
	
	LoadTemplate("trans-current");
	LoadTemplate("tabs-each");
	LoadTemplate("search-outline");
	LoadTemplate("search-each");
	LoadTemplate("result-action-add");
	LoadTemplate("result-action-none");
	
	var contHeight = windowHeight - 175;
	var btcont = document.getElementById("btguru_content");
	btcont.style.height = contHeight;
	
	
	
	btTabs[0] = new btTab(0, "Transmission", new btTransmissionEngine());
	btTabs[1] = new btTab(1, "Search", new btSearchEngine());
	
	RenderTabBar();
	
	btTabs[0].Render();
	//btTabs[1] = new btTab(1, "Search");
	
	//btTabs[0].element = document.getElementById("transmission_current");
	//btTabs[1].element = document.getElementById("results_tpb");
	
	//btTabs[1].element.style.display = "none";
	btTabs[0].element.style.width = "100%";
	
	setTimeout("TabUpdater()", 7500);
}

function TabUpdater()
{
	for(var et = 0; et < btTabs.length; et++)
	{
		btTabs[et].Update();
	}
	
	setTimeout("TabUpdater()", 7500);
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

	lnxhr.open("GET", "/?module=btguru&sub=template&template="+template, false);
	lnxhr.send(null);
	
	var ptid = btTemplateIDs.length;
	btTemplateIDs[ptid] = template;
	btTemplates[ptid] = lnxhr.responseText;

}

function Template(template)
{
	for(var et = 0; et < btTemplateIDs.length; et++)
	{
		if(btTemplateIDs[et] == template) { return btTemplates[et]; }
	}
	
	return "";
}

window.onload = InitializebtGuru;

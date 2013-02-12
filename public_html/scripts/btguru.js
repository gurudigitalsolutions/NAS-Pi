var UpdateTranDelayTime = 10000;
var kaktive = 0;
//var tccount = 0;

function DoSearch(usequery)
{
	var sbid = document.getElementById('query');
	var searchquery = "";
	if(usequery == "")
	{
		searchquery = sbid.value;
	}
	else { searchquery = usequery; }
	
	LoadPage("/?module=btguru&sub=search&query="+searchquery, 'results_tpb');
}

function EnterHandler(e)
{
	
	if(e.keyCode == 13)
	{
		var tb = document.getElementById('query');
		
		var evvedto = tb.value;
		//eval(tb.value);
		setTimeout("DoSearch('"+tb.value+"')", 250);
		tb.value = "";
		return false;
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
	
	LoadPageCallFunction("/?module=btguru&sub=addtorrent&torrentlink"+torrentlink, 'AddTorrentResponse', Array(resultid));
}

function AddTorrentResponse(restext, args)
{
	if(restext == ":)")
	{
		var torresdiv = document.getElementById("result_row_"+args[0]);
		torresdiv.style.background = '#80F080';
		//(document.getElementById('result_addtorrent_'+args[0])).innerHTML = "&lt;&lt;";
	}
	else if(restext == ":(")
	{
		var torresdiv = document.getElementById("result_row_"+args[0]);
		torresdiv.style.borderColor = "#CC3310";
		//(document.getElementById('result_addtorrent_'+args[0])).innerHTML = "---";
	}
}

function UpdateTransmissionStats()
{
	SingleTransmissionUpdate()
	setTimeout("UpdateTransmissionStats()", UpdateTranDelayTime);
}

function SingleTransmissionUpdate()
{
	LoadPage("/?module=btguru&sub=transmissionstate", 'transmission_current');
	//tccount++;
	//document.getElementById('tccount').innerHTML = tccount;
}

window.onload = UpdateTransmissionStats;

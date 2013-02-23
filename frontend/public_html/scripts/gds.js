var cactive = 0;
			
function LoadPage(url, output)
{
	if(cactive == 1)
	{
		setTimeout("LoadPage('"+url+"', '"+output+"')", 1200);
		//alert("LoadPage('"+url+"', '"+output+"')");
		return;
	}

	cactive = 1;

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
	
	cactive = 0;
}

function LoadPageCallFunction(url, funcname, args)
{
	if(cactive == 1)
	{
		setTimeout("LoadPageCallFunction('"+url+"', '"+funcname+"', '"+args+"')", 1200);
		return;
	}

	cactive = 1;

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

	
	cactive = 0;
	var fn = window[funcname];
	fn(xhaddtobuds.responseText, args);

}

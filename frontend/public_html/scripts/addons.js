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

function AddOnMoreInfo(modcode)
{
	var aodiv = document.getElementById("addons_moreinfo_"+modcode);
	var cdisp = aodiv.style.display.value;
	
	if(cdisp == "block") { aodiv.style.display = "none"; }
	else { aodiv.style.display = "block"; }
}

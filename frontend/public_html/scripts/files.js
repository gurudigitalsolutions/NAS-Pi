function ShowSourceDetails(sourcecode)
{
	var fsm = document.getElementById('files_source_more_'+sourcecode);
	var fsmlnk = document.getElementById('files_source_more_link_'+sourcecode);
	
	if(fsm.style.display == "block")
	{
		fsm.innerHTML = "";
		fsm.style.display = "none";
		fsmlnk.innerHTML = "More";
	}
	else/*(fsm.style.display == "none")*/
	{
		//	Right now we aren't showing any extra info for this source, so we need
		//	to load it and show it.
		fsm.style.display = "block";
		fsmlnk.innerHTML = "Less";
		LoadPage("/?module=files&sub=moreinfo&sourcecode="+sourcecode, 'files_source_more_'+sourcecode);
		
	}
}

function ConfirmDeleteSource(sourcecode)
{
	if(confirm("Are you sure you would like to delete the source '"+sourcecode+"'"))
	{
		document.location = "/?module=files&sub=deletesource&sourcecode="+sourcecode;
	}
}

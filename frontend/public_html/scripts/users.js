function ShowUserDetails(username)
{
	var fsm = document.getElementById('users_moreinfo_'+username);
	var fsmlnk = document.getElementById('users_manager_showmore_'+username);
	
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
		LoadPage("/?module=users&sub=moreinfo&username="+username, 'users_moreinfo_'+username);
		
	}
}

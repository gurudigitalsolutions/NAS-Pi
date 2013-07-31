var Player = new PiTunesPlayer();


function PiTunesPlayer()
{
	this.CurrentTrack = "";
	this.CurrentPos = 0;
	this.Playlist = Array();
	this.PlaylistLow = 0;
	this.TimeElapsed = 0;
	this.TimeRemaining = 0;
	this.cactive = 0;
	this.PlaylistTemplate = "";
	this.PlaylistRowTemplate = "";
	
	this.Initialize = function()
	{
		if (window.XMLHttpRequest)
		{// code for IE7+, Firefox, Chrome, Opera, Safari
			var lnxhr = new XMLHttpRequest();
		}
		else
		{// code for IE6, IE5
			var lnxhr = new ActiveXObject("Microsoft.XMLHTTP");
		}

		lnxhr.open("GET", "/?module=pitunes&sub=template&template=playlist", false);
		lnxhr.send(null);
		this.PlaylistTemplate = lnxhr.responseText;
		
		if (window.XMLHttpRequest)
		{// code for IE7+, Firefox, Chrome, Opera, Safari
			var lnxhr = new XMLHttpRequest();
		}
		else
		{// code for IE6, IE5
			var lnxhr = new ActiveXObject("Microsoft.XMLHTTP");
		}

		lnxhr.open("GET", "/?module=pitunes&sub=template&template=playlistrow", false);
		lnxhr.send(null);
		this.PlaylistRowTemplate = lnxhr.responseText;
		
		this.CurrentTrackFetcher();
		this.PlaylistFetcher(0, 4);
	}
	
	this.PlayPause = function()
	{
		this.CallPageNoOutput("pause");
	}
	
	this.Stop = function()
	{
		this.CallPageNoOutput("stop");
	}
	
	this.Next = function()
	{
		this.CallPageNoOutput("next");
	}
	
	this.Prev = function()
	{
		this.CallPageNoOutput("prev");
	}
	
	this.Rewind = function()
	{
		this.CallPageNoOutput("rewind");
	}
	
	this.FastForward = function()
	{
		this.CallPageNoOutput("fastforward");
	}
	
	this.CallPageNoOutput = function(args)
	{
		if (window.XMLHttpRequest)
		{// code for IE7+, Firefox, Chrome, Opera, Safari
			var lnxhr = new XMLHttpRequest();
		}
		else
		{// code for IE6, IE5
			var lnxhr = new ActiveXObject("Microsoft.XMLHTTP");
		}

		lnxhr.open("GET", "/?module=pitunes&sub=query&args="+args, false);
		lnxhr.send(null);

	}
	
	this.CurrentTrackFetcher = function()
	{
		if(this.cactive == 0) { this.cactive = 1; }
		else { setTimeout("Player.CurrentTrackFetcher()", 1300); return; }
		
		var xmlhttp = null;
		if(window.XMLHttpRequest) { xmlhttp = new XMLHttpRequest(); }
		else { xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); }
		
		xmlhttp.onreadystatechange=function()
		{
			if (xmlhttp.readyState==4 && xmlhttp.status==200)
			{
				
				Player.CurrentTrackHandler(xmlhttp.responseText);
			}
		}
		xmlhttp.open("GET","/?module=pitunes&sub=query&args=currentlyplaying",true);
		xmlhttp.send();
	}
	
	this.CurrentTrackHandler = function(currenttrack)
	{
		this.cactive = 0;
		var fprt = currenttrack.split("\t");
		
		var dd = document.getElementById("playlist_row_"+this.CurrentPos);
		if(dd != null) { dd.className = "playlist_row"; }
		this.CurrentPos = fprt[0];
		var dx = document.getElementById("playlist_row_"+this.CurrentPos);
		if(dx != null) { dx.className="playlist_row_current"; }
		
		currenttrack = currenttrack.substring(currenttrack.lastIndexOf("/") + 1);
		
		var cpbox = document.getElementById("pitunes_currentlyplaying");
		cpbox.innerHTML = currenttrack;
		this.CurrentTrack = currenttrack;
		
		setTimeout( function() { Player.CurrentTrackFetcher(); }, 6000);
	}
	
	this.PlaylistFetcher = function()
	{
		if(this.cactive == 0) { this.cactive = 1; }
		else { setTimeout("Player.PlaylistFetcher()", 1300); return; }
		
		var xmlhttp = null;
		if(window.XMLHttpRequest) { xmlhttp = new XMLHttpRequest(); }
		else { xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); }
		
		xmlhttp.onreadystatechange=function()
		{
			if (xmlhttp.readyState==4 && xmlhttp.status==200)
			{
				Player.PlaylistHandler(xmlhttp.responseText);
			}
		}
		xmlhttp.open("GET","/?module=pitunes&sub=query&args=getplaylist",true);
		xmlhttp.send();
	}
	
	this.PlaylistHandler = function(entries)
	{
		this.cactive = 0;
		
		this.Playlist = eval("("+entries+")");
		
		this.RenderPlaylist();
	}
	
	this.RenderPlaylist = function()
	{
		var allrows = "";
		for(var et = 0; et < this.Playlist.length; et++)
		{
			var trow = this.PlaylistRowTemplate;
			
			trow = trow.replace("[TRACK]", this.Playlist[et]);
			while(trow.indexOf("[INDEX]") != -1) { trow = trow.replace("[INDEX]", et); }
			
			allrows = allrows+trow;
		}
		
		var tmp = this.PlaylistTemplate;
		tmp = tmp.replace("[EACHROW]", allrows);
		
		document.getElementById("pitunes_playlist").innerHTML = tmp;
		
		document.getElementById("playlist_row_"+this.CurrentPos).className = "playlist_row_current";
	}
}

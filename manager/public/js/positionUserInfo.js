function setUserInfoPos() {
	var x = window.innerWidth;
	var ui;
	
	x = x - 18;	// maybe scroll bar
	
	ui = document.getElementById('userinfo');
	if (ui != null) {
		ui.style.right = "3px";
//		document.getElementById('userinfo').style.bottom = "3px";
		ui.style.bottom = 3 - window.pageYOffset + "px";
	}
}

window.onload = setUserInfoPos;
window.onresize = setUserInfoPos;
window.onscroll = setUserInfoPos;

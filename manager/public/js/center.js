function setCSS() {
	var x = window.innerWidth;

	x = x - 18;	// maybe scroll bar
	document.getElementById('center').style.width = x + "px";
	document.getElementById('center').style.marginLeft = "-" + x/2 + "px";
}

window.onload = setCSS;
window.onresize = setCSS;

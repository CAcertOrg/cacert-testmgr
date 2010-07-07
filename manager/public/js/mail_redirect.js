var delay = 2000;
setTimeout('redirect()', delay);

function redirect() {
	var returnto = document.getElementById('returnto');
	
	window.location = returnto.value;
}
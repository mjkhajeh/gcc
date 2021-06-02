document.getElementById("gcc_taxonomy").onchange = function() {
	var value = this.value;
	window.location.href = window.location.href+"&taxonomy="+value;
}
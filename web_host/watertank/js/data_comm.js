function data_request_timer(delay){
	window.setInterval(get_data, delay); //timer for initiating ajax request 

}

function get_data(){
	console.log("fetching data from server");

	$.get("/web_host/watertank/read_data.php?widget=info", 
	function(data, status){
    	document.getElementById("info").innerHTML = data;
  	});

}



<?php

global $db_server, $db_username, $db_pwd,$db_name;
 
$db_server='localhost';
$db_username='root';
$db_pwd=''; 
$db_name='water_level';


//------Tank Dimensions-------------------------
global $dia, $height;
$dia=104; //cm (Diameter of tank)
$height=116; //cm (height of tank)


//---------------utility functions-------------------------------------------------------
function calculate_volume($dia,$water_level){
	$radius=$dia/2;
	$vol_cubic_cm=(3.142) * $radius * $radius * $water_level;
	//$vol_litres = $vol_cubicInches * 0.0163871;
	$vol_litres = $vol_cubic_cm * 0.001;

	if($vol_litres<0)$vol_litres=0;
	return round($vol_litres,1);
}

function time_ago($timestamp) {
	   
	   $strTime = array("second", "minute", "hour", "day", "month", "year");
	   $length = array("60","60","24","30","12","10");

	   $currentTime = time();
	   if($currentTime >= $timestamp) {
			$diff     = time()- $timestamp;
			for($i = 0; $diff >= $length[$i] && $i < count($length)-1; $i++) {
			$diff = $diff / $length[$i];
			}

			$diff = round($diff);
			return $diff . " " . $strTime[$i] . "(s) ago ";
	   }
	}

function fetch_arr($result){
	$row = @mysqli_fetch_array($result);
	return $row;
}

function create_db_connection(){
	global $db_server, $db_username, $db_pwd,$db_name;
	
	
	if (($connection =mysqli_connect($db_server, $db_username, $db_pwd, $db_name))===FALSE)
 	die("<h3 style='color:red'>could not connect to the database server</h3>");

 	return $connection;
}


function close_db_connection($connection){
	mysqli_close($connection);
}


/*returns an 2-dimentional array of all the rows selected by the query */
function db_select_all($connection,$sql){
	
	$results = mysqli_query($connection,$sql);
	$k=0;
	$w1=50;
	
	while($row = fetch_arr($results))
	{	
		
		$i=0;
		while(@$row[$i]!=NULL){
			$subarr[$i]=$row[$i];
			$i=$i+1;

		}
		$arr2d[$k]=$subarr;
		$k=$k+1;
	}
	
	return @$arr2d;
}

function db_select_row($connection,$sql){
	
	$results = mysqli_query($connection,$sql);

	$row_count=mysqli_num_rows($results);

	if($row_count==1){
		$row = fetch_arr($results);
		return $row;
	}
	else{
		echo"<h3 style='color:red'>$sql <br> Query returns more than one row or zero rows</h3><br>";
	}	
	
	return @$array;
}


?>

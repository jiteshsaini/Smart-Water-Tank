<?php
/*
* Project: Smart Water Tank
* Created by: Jitesh Saini
*/

error_reporting(-1);

//----------database access----------------------------
include_once '../util/db_query.php';
include_once 'db_params_wt.php';
//-----------------------------------------------------

include_once 'util_functions_wt.php';

$sql="SELECT * FROM `level_log` WHERE 1 order by `timestamp` desc limit 1";
$row=db_select_row($sql);
//print_r($row); 	
$x_cm=$row['level'];
//$x_cm=round($x_cm,2);
//$x_inches=$x_cm * 0.393701;
$timestamp=$row['timestamp'];


global $dia, $height;
$water_level= $height-$x_cm;
$water_level= round($water_level,1);

$volume=calculate_volume($dia,$water_level);

$widget=@$_GET["widget"];

if($widget=="info"){
	$currentTime = time();
	$diff=$currentTime-$timestamp;
	$seconds_left=60-$diff;
	
	if($diff<5){
		$style="background-color:lightgreen";
		$msg="<h2>Data Arrived !!!!</h2>";
		$img="<img src='data.gif' alt='Data Arrived !!!' width='80%' height='100'>";
	}
	else{
		$style="background-color:white;color:grey";
		$msg="Data expected in:<br> <b style='color:brown;font-size:40px'>$seconds_left</b> seconds";
		$img="";
	}
	
	if($diff>60){
	   $str=timeago2($timestamp);
	   $ago="(<txt style='color:brown'>$str</txt>)";
	}
	else{
	   $ago="";
	}
	
	if($seconds_left < -4){
	    $msg="<p style='color:red;font-size:20px'>Data from sensor not available temporarily. Pl check after some time.</p>";
	}
	
	echo"<div style='$style'>
        	$msg $img
			<br><br>
			Last value: $x_cm <br>$ago
			<hr>
			Water Level:<br> <b style='color:blue'>$water_level cm</b><br>
		</div>
	";
	
}

if($widget=="tank_animation"){
	$str="&value=".$volume;
	echo"$str";
}


?>
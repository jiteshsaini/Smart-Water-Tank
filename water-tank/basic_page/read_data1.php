<?php
ini_set('display_errors', '1');

//----------database access----------------------------
include_once '../util.php';
//-----------------------------------------------------

$con=create_db_connection();

/* select the latest entry in the table */
$sql="SELECT * FROM `level_log` WHERE 1 order by `timestamp` desc limit 1";
$row=db_select_row($con,$sql);

close_db_connection($con);


$x_cm=floatval($row['level']); //this is the sensor reading i.e. the distance of empty part of tank

global $dia, $height; //these are defined in 'util.php' file
$water_level= $height-$x_cm; 
$water_level= round($water_level,1);

//$x_cm=round($x_cm,2);
//$x_inches=$x_cm * 0.393701;
$timestamp=$row['timestamp'];

$currentTime = time();
$diff=$currentTime-$timestamp; //how old the entry is 
$seconds_left=60-$diff;	//seconds left for the next value to arrive

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
   $str=time_ago($timestamp);
   $ago="(<txt style='color:brown'>$str</txt>)";
}
else{
   $ago="";
}

if($seconds_left < -4){
	$msg="<p style='color:red;font-size:20px'>Data from sensor not available temporarily. Pl check after some time.</p>";
}

$html="<div style='$style'>
	       	$msg $img
			<br><br>
			Last value: $x_cm <br>$ago
			<hr>
			Water Level:<br> 
			<b style='color:blue'>$water_level cm</b>
			<br>
		</div>
";

echo $html;
	
?>
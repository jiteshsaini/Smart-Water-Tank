<?php
ini_set('display_errors', '1');

//----------database access----------------------------
include_once '../util.php';
//-----------------------------------------------------

$con=create_db_connection();

$sql="SELECT * FROM `level_log` WHERE 1 order by `timestamp` desc limit 1";
$row=db_select_row($con,$sql);
//print_r($row); 	

close_db_connection($con);

$x_cm=$row['level'];

global $dia, $height;
$water_level= $height-$x_cm;
$water_level= round($water_level,1);

$volume=calculate_volume($dia,$water_level);

$str="&value=".$volume;

echo"$str";

?>
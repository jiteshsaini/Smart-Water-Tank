<?php
error_reporting(-1);
date_default_timezone_set("Asia/Kolkata");

$widget=@$_GET["widget"];

if($widget=="info"){
	$currentTime = time();
	
	$hr_min=date('H:i', $currentTime);
	$second= date('s', $currentTime);
	
	echo"<div>
	        Server Time: $hr_min:<b style='color:brown'>$second</b>
		</div>
	";
}

if($widget=="tank_animation"){
	$currentTime = time();
    $second= date('s', $currentTime);
    $str="&value=".$second;
    echo"$str";
}


?>
<!doctype html>
<html lang="en">

<head>
<style>
	a{text-decoration:none;}
</style>
	
</head>

<body>


<?php

ini_set('display_errors', '1');


$date=@$_GET['date'];
if($date==""){
    $date=date('Y-m-d', time());
}

/* --------- Render the graph ----------------------*/
$src='graph.php?date='.$date;
echo"<iframe height='320px' width='100%' src='$src' name='iframe_b' style='border:1px solid lightgray;'></iframe>";
    

/* -------- show the last 7 dates as hyperlinks ------------- */
$currentTime = time();
$seconds_in_day=24*60*60;
echo"<table><tr>";
for($i=0;$i<7;$i++){
    $t=$currentTime - $i * $seconds_in_day;
    $dt=date('Y-m-d', $t);
    $dt1=date('d M y', $t);
        
    $link="<a href='index.php?date=$dt'>$dt1</a>";
        
    echo"<td style='border:1px solid grey;padding:5px;'>$link</td>";
}
echo"</tr></table>";

echo"<p style='float:right'>Sample data: <button><a href='index.php?date=2020-07-26'>26 Jul 2020</a></button>";    

//27juj21,30jul 
?>

</body>
</html>
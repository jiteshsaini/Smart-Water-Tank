<!doctype html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body { margin: 6px; font: 14px system-ui, -apple-system, sans-serif; }
    a { text-decoration: none; }

    /* PHPlot draws a 600 by 300 picture. On a narrow screen it is scaled to
       fit rather than redrawn, so it stays readable without the page having to
       tell the graph how wide the phone is. */
    iframe.plot { width: 100%; max-width: 620px; height: 320px; border: 1px solid lightgray; }

    table { border-collapse: collapse; }
    td { border: 1px solid #ccc; padding: 5px 7px; font-size: 13px; }
</style>

</head>

<body>


<?php

ini_set('display_errors', '1');


$date = $_GET['date'] ?? '';
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
    $date = date('Y-m-d');
}

/* --------- Render the graph ----------------------*/
$src='graph.php?date='.$date;
echo"<iframe class='plot' src='$src' name='iframe_b'></iframe>";
    

/* -------- show the last 7 dates as hyperlinks ------------- */
$currentTime = time();
$seconds_in_day=24*60*60;
echo"<table><tr>";
for($i=0;$i<7;$i++){
    $t=$currentTime - $i * $seconds_in_day;
    $dt=date('Y-m-d', $t);
    $dt1=date('d M y', $t);
        
    $link="<a href='index.php?date=$dt'>$dt1</a>";
        
    echo"<td>$link</td>";
}
echo"</tr></table>";

/*
One day of real readings ships with the project, dated 1 Sep 2026, so there is
always a complete day to look at. Put it beside today and you can see the
difference between the sample and what your own sensor is recording.
*/
echo"<p style='float:right'>Sample data: <button><a href='index.php?date=2026-09-01'>01 Sep 26</a></button>";
?>

</body>
</html>
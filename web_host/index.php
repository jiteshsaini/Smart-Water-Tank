<?php
/*
* Project: Smart Water Tank
* Created by: Jitesh Saini
*/


error_reporting(-1);//report all errors during execution

global $host;
//$host="https://helloworld.co.in";
$host="http://localhost";

global $realtime_path,$simulation_path;

$realtime_path = "$host/web_host/watertank";

$simulation_path = "$host/web_host/watertank_simulation";


display_data();


function display_data(){
    
    //$uri = \Drupal::request()->getRequestUri();
    $uri= $_SERVER['REQUEST_URI'];    
   // echo"uri: $uri<br>";
    
    $xx=@explode('?',$uri);
    $params=@$xx[1];
    
   // echo"param: $params<br>";
    
    $yy=@explode('&',$params);
    
    $zz1=@explode('=',$yy[0]);$page=@$zz1[1];
    $zz2=@explode('=',$yy[1]);$date=@$zz2[1];
    
    //echo"page: $page<br>";
    //echo"date: $date<br>";
    
    echo"<hr>";
    
    if ($page=="realtime" or $page==""){
        display_real_time($date);
    }
    
    if ($page=="simulation"){
        display_simulation();
    }
    
    echo"<hr>";
     
}

function display_real_time($date){
    global $host;

    echo"
        <p>The dashboard shown below displays real time water level of my house's overhead tank. Tank level is updated every minute on arrival of sensor data. 
        The graph (shown below the tank animation) displays the water level readings for a selected date. 
        Y-axis indicates the volume of water left in the tank and X-axis denotes hours in a day</p>
    ";
    
    echo"<p>Since the real-time data changes marginally in a minute, you may find no change in the tank level. 
    To appreciate the tank animation, check out this <a href='$host/web_host?page=simulation&date=0'>simulation</a>. Or, just check this page again after few hours to notice the change.</p>";
    
    show_chart_info();
    
    show_graph($date);
    
    show_dates();
    
    
}

function display_simulation(){
    global $host;
    echo"<p>In this simulation, the remote sensor data has been replaced with a value that changes at much faster rate. This value is Server's time's 'Second' value.
		This value is fed to the tank animation. Since 'Second' value changes from 0 to 60, the water level in this animation also varies between 0-60.</p>";
		
    echo"<p>Go back to <a href='$host/web_host'>real-time</a> view</p>";
    
    show_chart_info_2();
}

//-------------------------display_simulation() supporting function--------------------------------------

function show_chart_info_2(){
    global $simulation_path;
    $path=$simulation_path."/dashboard_simulation.php";
    //echo"$path<br>";
    echo"<iframe height='500px' width='100%' src='$path' name='iframe_a'></iframe>";
    echo"<br>";
}

//--------------------------------------------------------------------------------------

//=================display_real_time() supporting function==========================================
function show_chart_info(){
    global $realtime_path;
    $path=$realtime_path."/dashboard.php";
    //echo"$path<br>";
    echo"<iframe height='500px' width='100%' src='$path' name='iframe_a'></iframe>";
    echo"<br>";
}
    
function show_graph($date){
    
    if($date==""){
      //$date=date('Y-m-d', time());
        $date="2020-06-26";
    }
    
    global $realtime_path;
    $path=$realtime_path.'/phplot/graph.php?date='.$date;
    
    //echo"path = $path<br>";
    
    //echo"<iframe height='300px' width='100%' src='$realtime_path/phplot/graph.php?date=$date' name='iframe_b' style='border:1px solid green;'></iframe>";
    echo"<iframe height='320px' width='100%' src='$path' name='iframe_b' style='border:1px solid lightgray;'></iframe>";
}

function show_dates(){
    /*
    $currentTime = time();
    $seconds_in_day=24*60*60;
    echo"<table><tr>";
    for($i=0;$i<7;$i++){
        $t=$currentTime - $i * $seconds_in_day;
        $dt=date('Y-m-d', $t);
        $dt1=date('d M y', $t);
        
        global $host;
        $link="<a href='$host/water-tank?page=realtime&date=$dt'>$dt1</a>";
        //echo"link: $link<br>";
        echo"<td style='border:1px solid grey;padding:5px'>$link</td>";
    }
    echo"</tr></table>";
    */

    echo"<table><tr>";
        
        $dt1="2020-06-18";
        $dt2="2020-06-20";
        $dt3="2020-06-26";
        
        global $host;
        $link1="<a href='$host/web_host/index.php?page=realtime&date=$dt1'>18 Jun 20</a>";
        $link2="<a href='$host/web_host/index.php?page=realtime&date=$dt2'>20 Jun 20</a>";
        $link3="<a href='$host/web_host/index.php?page=realtime&date=$dt3'>26 Jun 20</a>";

        echo"<td style='border:1px solid grey;padding:5px'>$link1</td>";
        echo"<td style='border:1px solid grey;padding:5px'>$link2</td>";
        echo"<td style='border:1px solid grey;padding:5px'>$link3</td>";
    
    echo"</tr></table>";

}

//=========================================================================================


/* New way of accessing URL in Drupal 8
$uri = \Drupal::request()->getRequestUri();
$uri_without_query_string = \Drupal::request()->getPathInfo();
*/
?>
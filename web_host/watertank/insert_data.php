<?php
/*
* Project: Smart Water Tank
* Created by: Jitesh Saini
*/

error_reporting(-1);
date_default_timezone_set("Asia/Kolkata");

//----------database access----------------------------
include_once '../util/db_query.php';
include_once 'db_params_wt.php';
//-----------------------------------------------------


echo"Water level Logging<br>";

$val=$_GET["level"];
$passcode=$_GET["passcode"];

if ($passcode!="xyz"){
    echo"passcode does not match<br>";
    exit();
}

//$val="10";
insert_in_db($val);

function insert_in_db($val){   
   echo"val=$val<br>";
   
    $connection=create_db_connection();
    
    $t=time();
    $date_time= date('Y-m-d H:i:s', $t); 
    echo "date_time=$date_time<br>";

    $sql="INSERT INTO `level_log`(`level`, `timestamp`, `date_time`)
                      VALUES ('$val','$t', '$date_time')";
   
    $result = get_results($connection,$sql);
    
         if ( !$result ) {
            die(mysql_error()."\n".$sql);
            echo" !!!<br> ";
            //return 0;
         }
    
        if ( $result ) {
            echo" successfully inserted<br> ";
            //return 1;
      }
 
    close_db_connection($connection);
    
}

?>


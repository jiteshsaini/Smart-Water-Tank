<?php
/*
* Project: Smart Water Tank
* Created by: Jitesh Saini
*/

function row_count($results){
	$row_count=mysqli_num_rows($results);
	return $row_count;
}

function fetch_arr($result){
	$row = @mysqli_fetch_array($result);
	return $row;
}


function create_db_connection(){
	global $db_server, $db_username, $db_pwd,$db_name;
	if (($connection =mysqli_connect($db_server, $db_username, $db_pwd, $db_name))===FALSE)
 	die("<h2 style='color:red'>could not connect to the database server</h2>");

 	return $connection;
}

function close_db_connection($connection){
	mysqli_close($connection);
}

function get_results($connection,$sql){
	$results =mysqli_query($connection,$sql);
	return $results;
}

//================================================================

//================DB Query Functions================================

/*returns a 2-dimentional array (as shown in phpmyadmin)*/
function db_select_all($sql){
	$connection=create_db_connection();

	$results = get_results($connection,$sql);
	$k=0;
	$w1=50;
	//echo"<table border='1'>";
	while($row = fetch_arr($results))
	{	
		//echo"<tr>";
		$i=0;
		while(@$row[$i]!=NULL){
		//	echo"<td style='width:$w1;color:black;'>$row[$i]</td>";
			$subarr[$i]=$row[$i];
			$i=$i+1;

		}
		//echo"</tr>";	
		$arr2d[$k]=$subarr;
		$k=$k+1;
	}
	//echo"</table>";
	//echo"No of rows: <b style='color:red'>$k</b><br>";

	close_db_connection($connection);

	return @$arr2d;
}

/* prints a 2-dimentional array */
function print_2d_array($arr2d){
  echo"<table border='1'>";
  $w1=60;
  for($i=0;$i<sizeof($arr2d);$i++){ 
    echo"<tr>";
   // print_size_array($arr[$i]);
    $subarr=$arr2d[$i];
    
    for($k=0;$k<sizeof($subarr);$k++){
      //echo"($subarr[$k])";
      echo"<td style='width:$w1;background-color:lightyellow;'>$subarr[$k]</td>";
    }
    echo"</tr>";  
  }
  echo"</table>";

}

/* $sql: select query should return only one column name (confirm result of query through phpmyadmin)*/
function db_select_column($sql){
	
	$connection=create_db_connection();

	$results = get_results($connection,$sql);

	$row_count=row_count($results);

	if($row_count==0)
		return;
		
	$array=array();$k=0;
	while($row = fetch_arr($results))
		{
			$array[$k]=$row[0];
			$k=$k+1;
		}
	
	close_db_connection($connection);
	
	return @$array;
}


/* $sql: select query should only return one row (confirm result of query through phpmyadmin)*/
function db_select_row($sql){
	
	$connection=create_db_connection();

	$results = get_results($connection,$sql);

	$row_count=row_count($results);//echo"cnt: $row_count<br>";

	if($row_count==1){
		$row = fetch_arr($results);
		return $row;
	}
	else{
		echo"<h3 style='color:red'>Query returns more than one row</h3><br>";
	}	
	
	close_db_connection($connection);
	
	return @$array;
}

//================================================================================

?>
<?php
ini_set('display_errors', '1');
require_once 'phplot.php';
require_once '../util.php';

$con=create_db_connection();

$dt_graph=$_GET["date"];
$sql="SELECT `date_time`, `level` FROM `level_log` where `date_time` like '%$dt_graph%' ORDER BY `date_time`";
$arr2d=db_select_all($con,$sql);


if($arr2d!=null){
  $sz=sizeof($arr2d);
}
else{
  $sz=0;
}

/*
* converting 2d array to associative array.  
*/
for($i=0;$i<$sz;$i++)
  {
    //echo"$data[$i]<br>";
	$arr=$arr2d[$i];
	$dt = strtotime($arr[0]);
	$t= date('H:i', $dt);
	
	$ass_arr[$t]=$arr[1];
	//echo"$arr[0] == $arr[1], $t <br>-----<br>";
  } 
 
//print_ass_array($ass_arr);

/*sensor data is inserted in every 1 minute. Sometimes due to connectivity issues, the data is not inserted for all the minutes in a day.
* This function pads up such missing values with zero. Now for all 1440 minutes of the day, we have a value.
*/
for($i=0;$i<1440;$i++)
  {
    //echo gmdate("H:i", $i*60);echo "<br>"
    $min=gmdate("H:i", $i*60); //create date formate from an integer 
	@$val=$ass_arr[$min]; //get the corresponding sensor data value from associative array ($ass_arr) for Key (minute)
	if($val==""){ //if there is no value found for this key (minute), it means data not recorded for this minute
		$ass_array_padded[$min]=0; //In a new associative array, storing zero for this key
	}
	else{ //if there is value existing for this key (minute), then calculate volume and store in new associavtive array 
		global $dia, $height;
		$h=$height-$val; 
		$vol=calculate_volume($dia,$h);
		$ass_array_padded[$min]=$vol;
	}
    //echo"$min = $val<br>";
  } 

/*
* We can plot all 1440 data points or we can take some fraction of it.
This function selects the samples from the $ass_array_padded as per the value of variable $sample
for e.g. 
if $sample = 1 ,then all 1440 values are plotted
if $sample = 5, then 1440/5 or 288 values are plotted
try changing this number and see the effect on the density of graph
*/
$sample=5;
$arr2d_1=extract_samples_from_assArray($ass_array_padded, $sample); //take every 5th value

/*
* first value of each array will be displayed as X-axis marking. This function keeps only 24 values which denote the start of each hour of the day.
*/
$arr2d_2=modify_sampled_2d_array($arr2d_1);

/*
* At this stage, the data is ready for the graph. Simply pass the $arr2d_2 to the phplot. It will generate the graph.
*/
draw_graph($arr2d_2);

//================functions=================================================
function draw_graph($arr2d){
    $plot = new PHPlot(600, 300); //size of graph 
    $plot->SetImageBorderType('plain');

    $plot->SetPlotType('thinbarline');
    $plot->SetDataType('text-data');
    $plot->SetNumXTicks(24);
    $plot->SetDataValues($arr2d);

    # Main plot title:
    $dt_graph=$_GET["date"];
    $dt= date('d M y', strtotime($dt_graph));
    $title='Water Tank Level : '.$dt;
    $plot->SetTitle($title);

    $plot->DrawGraph();
}


function extract_samples_from_assArray($ass_array_padded, $interval){
  $k=0;
  $hop=0; // samples from data 
  
  foreach($ass_array_padded as $t=>$val){

    if($hop==$interval){
      
      $subarr_mod[0]=$t;
      $subarr_mod[1]=$val;

      $mod_2d_array[$k]=$subarr_mod;
      $k=$k+1;
      $hop=0;

    } 

    $hop=$hop+1;

  }
  return $mod_2d_array;
}

function modify_sampled_2d_array($arr2d){

  for($i=0;$i<sizeof($arr2d);$i++){
    $arr=$arr2d[$i];
    
    $xx=explode(":", $arr[0]);
    $minute=$xx[1];

   // echo"$arr[0] : $arr[1] *** $minute<br>";

    if($minute=="00")
      $x_annotation=$xx[0];
    else
      $x_annotation="";

    $subarr_mod[0]=$x_annotation;
    $subarr_mod[1]=$arr[1];

    $mod_2d_array[$i]=$subarr_mod;
  }

  return $mod_2d_array;
}


?>
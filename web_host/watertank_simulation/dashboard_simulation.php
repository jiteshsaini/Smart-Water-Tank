<!--
Project: Smart Water Tank
Created by: Jitesh Saini
-->

<html>
<head>
    <title>Simulation</title>
	
	<script type="text/javascript" src="https://cdn.fusioncharts.com/fusioncharts/latest/fusioncharts.js"></script>
    <script type="text/javascript" src="https://cdn.fusioncharts.com/fusioncharts/latest/themes/fusioncharts.theme.fusion.js"></script>
  
	<script type="text/javascript" src="js/tank_animation_simulation.js"></script>
	<script type="text/javascript" src="js/data_comm_simulation.js"></script>
    <script type="text/javascript" src='js/jquery3.min.js'></script>
  
    <link rel="stylesheet" type="text/css" href="css/dashboard_simulation.css">
</head>
<body onload="data_request_timer(1000)">
<?php
echo"<div id='box_outer'>";
  
    echo"<div class='box_1' align='center'>";
        echo"<h3>Tank Level Simulation</h3>";
    echo"</div>";
    
    echo"<div class='box_1' align='center'>";
         echo"<data id='info'></data>";
    echo"</div>";
    
    echo"<div class='box_1' align='center'>";
	    echo"<div id='chart-container'>Reload Page if you don't see animation</div>";
    echo"</div>";


echo"</div>";


?>
</body>
</html>
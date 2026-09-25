<?php
/*
The tank drawing.

The shape and the capacity come from the settings page, so nothing here needs
changing when someone sets up a tank of their own.
*/

require_once __DIR__ . '/../util.php';

$tank = tank_settings();
$full = tank_capacity($tank);

if ($full <= 0) {
    $full = 800;        // nothing set yet, so the scale still has numbers on it
}
?>
<!doctype html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>

<div id='chart-container' style='color:grey;font:14px sans-serif;padding:20px'>Drawing the tank...</div>

<!--
The tank is drawn by a widget on helloworld.co.in, the same way this page used
to load a charting library from someone else's site. Your readings never leave
your own server: the widget asks this page's own read_data2.php for them.

The number after ?v= lets your browser keep the file until the widget changes.
-->
<script src="https://helloworld.co.in/demo/widgets/tank.js?v=7"></script>
<script>
    HWTank.render({
        container: "chart-container",
        dataUrl:   "read_data2.php",                                        // answers with: &value=433.3
        shape:     "<?= $tank['shape'] === 'box' ? 'box' : 'cylinder' ?>",  // from the Tank size page
        width:     <?= $tank['shape'] === 'box' ? $tank['length'] : $tank['diameter'] ?>,
        breadth:   <?= $tank['breadth'] ?>,
        depth:     <?= $tank['depth'] ?>,
        full:      <?= $full ?>,                                            // litres, from your measurements
        refresh:   15                                                       // seconds between readings
    });
</script>

</body>
</html>

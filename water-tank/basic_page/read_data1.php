<?php
ini_set('display_errors', '1');

require_once __DIR__ . '/../util.php';

$con = create_db_connection();

// The newest row is the highest id, which is the primary key. Sorting by the
// time column instead would sort the whole table to find one row.
// The age comes from the database, which wrote the time and knows its own
// clock. Working it out in PHP needs both to agree on a time zone, and when
// they did not, every reading looked as though it had arrived in the future,
// so the arrival banner never went away.
$row = db_select_row($con, "SELECT `level`, `date_time`,
                                   TIMESTAMPDIFF(SECOND, `date_time`, NOW()) AS age
                            FROM `level_log` ORDER BY `id` DESC LIMIT 1");

close_db_connection($con);

if (!$row) {
    echo "<div style='color:grey'>No readings yet. Once the sensor sends its first one, it appears here.</div>";
    return;
}

$x_cm = floatval($row['level']);   // the distance from the sensor down to the water

$water_level = water_depth($x_cm);

$diff = max(0, (int) $row['age']);   // how old the reading is, in seconds
$seconds_left = 60 - $diff;          // until the next one is due

if ($diff < 5) {
    $style = "background-color:lightgreen";
    $msg = "";   // the badge below says it, once is enough
    // Drawn rather than downloaded: this used to be a 1.4 MB animated gif of an
    // orange circle, four times larger than the space it was shown in.
    $img = "<div class='arrived'>Data Arrived</div>";
} else {
    $style = "background-color:white;color:grey";
    $msg = "Data expected in:<br> <b style='color:brown;font-size:40px'>$seconds_left</b> seconds";
    $img = "";
}

if ($diff > 60) {
    $str = time_ago($diff);
    $ago = "(<txt style='color:brown'>$str</txt>)";
} else {
    $ago = "";
}

if ($seconds_left < -4) {
    $msg = "<p style='color:red;font-size:20px'>Data from sensor not available temporarily. Pl check after some time.</p>";
}

echo "<div style='$style'>
        $msg $img
        <br><br>
        Last value: $x_cm <br>$ago
        <hr>
        Water Level:<br>
        <b style='color:blue'>$water_level cm</b>
        <br>
    </div>";

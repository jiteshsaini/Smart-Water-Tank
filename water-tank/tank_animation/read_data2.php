<?php
ini_set('display_errors', '1');

require_once __DIR__ . '/../util.php';

$con = create_db_connection();

// The newest row is the highest id, which is the primary key. Sorting by the
// time column instead would sort the whole table to find one row, which on a
// tank that has been running for a year takes about half a second, every two
// seconds, for every open browser.
$row = db_select_row($con, "SELECT * FROM `level_log` ORDER BY `id` DESC LIMIT 1");

close_db_connection($con);

$x_cm = $row ? floatval($row['level']) : 0;

$water_level = water_depth($x_cm);

$volume = $row ? calculate_volume($water_level) : 0;

// The tank animation expects exactly this shape.
echo "&value=" . $volume;

<?php
/*
The address the Raspberry Pi sends a reading to, once a minute:

    http://<this server>/water-tank/insert_data.php?dist=46.163

dist is what the ultrasonic sensor measured: the distance from the sensor down
to the surface of the water, in centimetres. The dashboard works out the depth
and the volume from it, using the tank size in config.php.
*/

date_default_timezone_set("Asia/Kolkata");   // set your own time zone here

require_once __DIR__ . '/util.php';

$dist = $_GET['dist'] ?? '';

// The reading arrives over the network, so it is checked before it goes
// anywhere near the database. A value taken straight from a URL and pasted
// into an SQL string is how a project like this gets broken into.
if (!is_numeric($dist)) {
    http_response_code(400);
    exit("dist must be a number, for example 46.163\n");
}

$dist = round((float) $dist, 3);

$connection = create_db_connection();

// Prepared, so the reading stays a value and can never become part of the
// statement, whatever it contains.
$statement = mysqli_prepare($connection, "INSERT INTO `level_log` (`level`, `date_time`) VALUES (?, NOW())");
mysqli_stmt_bind_param($statement, "d", $dist);
$stored = mysqli_stmt_execute($statement);
mysqli_stmt_close($statement);

close_db_connection($connection);

if (!$stored) {
    http_response_code(500);
    exit("the reading could not be stored\n");
}

echo "stored " . $dist . " cm at " . date('Y-m-d H:i:s') . "\n";

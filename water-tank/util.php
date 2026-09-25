<?php
/*
Shared helpers: the database connection, and turning a distance into a volume.

The settings live in config.php so that updating the code never overwrites what
you configured. config.sample.php shows what belongs in it.
*/

$config_file = __DIR__ . '/config.php';
if (!is_file($config_file)) {
    die("<h3 style='color:red'>config.php is missing. Copy config.sample.php to config.php and fill it in.</h3>");
}
require_once $config_file;




/*
Takes an age in seconds, not a moment in time.

The database works out how old a reading is, because it wrote the timestamp and
knows what its own clock meant. Comparing a stored time against PHP's clock
needs both to agree on a time zone, and when they did not, every reading looked
like it had arrived in the future.
*/
function time_ago($seconds) {
    $names = array("second", "minute", "hour", "day", "month", "year");
    $length = array(60, 60, 24, 30, 12, 10);

    $seconds = (int) $seconds;
    if ($seconds < 0) {
        return "";
    }

    for ($i = 0; $seconds >= $length[$i] && $i < count($length) - 1; $i++) {
        $seconds = $seconds / $length[$i];
    }
    return round($seconds) . " " . $names[$i] . "(s) ago ";
}


function create_db_connection() {
    global $db_server, $db_username, $db_pwd, $db_name;

    try {
        return mysqli_connect($db_server, $db_username, $db_pwd, $db_name);
    } catch (mysqli_sql_exception $e) {
        die("<h3 style='color:red'>Could not reach the database. Check the settings in config.php.</h3>");
    }
}


function close_db_connection($connection) {
    mysqli_close($connection);
}


/* Every row the query returns, each one as a plain numbered array. */
function db_select_all($connection, $sql) {
    $results = mysqli_query($connection, $sql);
    $rows = array();

    while ($row = mysqli_fetch_row($results)) {
        $rows[] = $row;
    }
    return $rows;
}


/*
The first row, or null.

No rows is an ordinary state here: a freshly installed tank has recorded
nothing yet. The caller decides what to show, rather than this function
printing an error into the middle of someone else's page.
*/
function db_select_row($connection, $sql) {
    $results = mysqli_query($connection, $sql);
    $row = mysqli_fetch_assoc($results);

    return $row ? $row : null;
}

/*
The tank, as set on the settings page.

Read once and kept for the rest of the request. If the settings table is not
there, the values in config.php are used instead, so an older install still
works until the installer is run again.
*/
function tank_settings($connection = null) {
    static $tank = null;

    if ($tank !== null) {
        return $tank;
    }

    global $dia, $height;

    $tank = array(
        'shape'    => 'cylinder',
        'diameter' => isset($dia) ? $dia : 104,
        'length'   => 0,
        'breadth'  => 0,
        'depth'    => isset($height) ? $height : 116,
    );

    $mine = ($connection === null);
    if ($mine) {
        $connection = create_db_connection();
    }

    try {
        $rows = mysqli_query($connection, "SELECT `name`, `value` FROM `settings`");
        while ($row = mysqli_fetch_assoc($rows)) {
            $tank[$row['name']] = $row['value'];
        }
    } catch (mysqli_sql_exception $e) {
        // no settings table yet, keep the values from config.php
    }

    if ($mine) {
        close_db_connection($connection);
    }

    return $tank;
}


/* How deep the water is, from a sensor reading. */
function water_depth($distance) {
    $tank = tank_settings();
    return round($tank['depth'] - $distance, 1);
}


/* Litres held by a given depth of water. */
function calculate_volume($water_depth, $tank = null) {
    if ($tank === null) {
        $tank = tank_settings();
    }
    if ($water_depth <= 0) {
        return 0;
    }

    if ($tank['shape'] === 'box') {
        $cubic_cm = $tank['length'] * $tank['breadth'] * $water_depth;
    } else {
        $radius = $tank['diameter'] / 2;
        $cubic_cm = M_PI * $radius * $radius * $water_depth;
    }

    return round($cubic_cm / 1000, 1);
}


/* Litres when it is full. The scale on the dashboard runs up to this. */
function tank_capacity($tank = null) {
    if ($tank === null) {
        $tank = tank_settings();
    }
    return calculate_volume($tank['depth'], $tank);
}

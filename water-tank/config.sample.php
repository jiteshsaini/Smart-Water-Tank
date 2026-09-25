<?php
/*
Settings for the Smart Water Tank dashboard.

Copy this file to config.php and fill it in. If you used the installer,
setup_water_tank.sh, it has already written config.php with a database account
it created, and it will never overwrite that file, so what you change survives
an update.
*/

$db_server   = 'localhost';
$db_username = 'water_tank';
$db_pwd      = '';
$db_name     = 'water_level';

// Guards the tank size page. Pick four digits of your own.
$settings_pin = "";

// The shape and size of the tank are set on that page and kept in the database.
// These two are used only when that table is missing, which happens on an
// install made before the page existed.
$dia    = 104;   // inside diameter
$height = 116;   // from the sensor down to the bottom of the tank

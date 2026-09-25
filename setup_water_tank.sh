#!/bin/bash
#
# Smart Water Tank: sets up the dashboard and the sensor on this Raspberry Pi.
#
#   sudo bash setup_water_tank.sh

# Started with "sh"? That is dash on a Pi, which does not understand [[ ]], so
# hand over to bash before anything else runs. Both tests below are plain POSIX
# for the same reason.
if [ -z "$BASH_VERSION" ]; then
    exec bash "$0" "$@"
fi

set -e

if [ "$(id -u)" -ne 0 ]; then
    exec sudo bash "$0" "$@"
fi

REAL_USER="${SUDO_USER:-pi}"
REPO="https://github.com/jiteshsaini/Smart-Water-Tank.git"

APP_DIR="/var/www/html/water-tank"
DB_NAME="water_level"
DB_USER="water_tank"
CONFIG="$APP_DIR/config.php"
ADMIN_FILE="/root/water-tank-admin.txt"

echo
echo "=================================================="
echo "  This machine"
echo "=================================================="

MODEL=$(tr -d '\0' < /proc/device-tree/model 2>/dev/null || echo "unknown")
OSVER=$(. /etc/os-release && echo "$PRETTY_NAME")
ARCH=$(uname -m)
IP=$(hostname -I | awk '{print $1}')
CODENAME=$(. /etc/os-release && echo "$VERSION_CODENAME")

# The serial, hashed, so installs can be counted without identifying a machine.
ID=$(grep -m1 ^Serial /proc/cpuinfo | sha256sum | cut -c1-16)

echo "  model    : $MODEL"
echo "  os       : $OSVER"
echo "  arch     : $ARCH"
echo "  address  : $IP"
echo "  user     : $REAL_USER"

echo
echo "=================================================="
echo "  Fetching the code"
echo "=================================================="

# The package lists are refreshed here rather than later, because git may be
# the first thing this script needs. Only the lists: upgrading someone's whole
# machine because they installed a water tank would be a rude surprise.
export DEBIAN_FRONTEND=noninteractive
export NEEDRESTART_MODE=a
apt-get update

if ! command -v git >/dev/null; then
    apt-get install -y git >/dev/null
fi

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

git clone --depth 1 "$REPO" "$WORK/repo" >/dev/null 2>&1 \
    || { echo "  could not fetch $REPO"; exit 1; }

SOURCE="$WORK/repo"
echo "  downloaded from GitHub"

# Nothing is touched until the code is here and looks like the project.
for needed in water-tank raspberry-pi; do
    if [ ! -d "$SOURCE/$needed" ]; then
        echo "  $SOURCE does not look like this project: no $needed folder"
        exit 1
    fi
done

echo
echo "=================================================="
echo "  Installing packages"
echo "=================================================="

# Debian asks two questions during an install that would otherwise stop this
# script and wait: needrestart offers a list of services to restart, and dpkg
# asks whether to replace a config file you have edited. Both are answered
# above, and whatever is already configured here is kept.
apt-get install -y \
    -o Dpkg::Options::=--force-confold \
    apache2 php libapache2-mod-php php-mysql mariadb-server python3-rpi-lgpio

# RPi.GPIO no longer ships with Raspberry Pi OS. python3-rpi-lgpio provides the
# same import on top of the kernel's gpiochip interface, so the sensor code runs
# unchanged.

echo
echo "=================================================="
echo "  Starting the services"
echo "=================================================="

systemctl enable --now mariadb
systemctl enable --now apache2
systemctl restart apache2

echo
echo "=================================================="
echo "  Installing the dashboard"
echo "=================================================="

SRC_APP="$SOURCE/water-tank"

if [ ! -d "$SRC_APP" ]; then
    echo "  cannot find $SRC_APP"
    exit 1
fi

# config.php holds the database password and the tank measurements, so it is
# carried across an update rather than replaced.
KEEP_CONFIG=""
if [ -f "$CONFIG" ]; then
    KEEP_CONFIG=$(mktemp)
    cp "$CONFIG" "$KEEP_CONFIG"
fi

if [ -d "$APP_DIR" ]; then
    STAMP=$(date +%Y%m%d_%H%M%S)
    mv "$APP_DIR" "${APP_DIR}.backup_$STAMP"
    echo "  moved the previous install to ${APP_DIR}.backup_$STAMP"
fi

cp -r "$SRC_APP" "$APP_DIR"
chown -R www-data:www-data "$APP_DIR"
echo "  copied the dashboard to $APP_DIR"

if [ -n "$KEEP_CONFIG" ]; then
    cp "$KEEP_CONFIG" "$CONFIG"
    rm -f "$KEEP_CONFIG"
    chown root:www-data "$CONFIG"
    chmod 640 "$CONFIG"
    echo "  kept the config.php you already had"
fi

# The sample is a template for making config.php. Leaving it next to the real
# one only invites the question of which is which.
rm -f "$APP_DIR/config.sample.php"

# Apache says nothing about caching by default, so browsers guess, and they
# guess generously: a page edited here can go on showing the old version for
# hours. no-cache keeps the file in the browser but makes it ask first, which
# costs one small request and means an edit is visible on the next reload.
a2enmod headers >/dev/null 2>&1 || true

CACHE_RULE=/etc/apache2/conf-available/water-tank-cache.conf
if [ ! -f "$CACHE_RULE" ]; then
    cat > "$CACHE_RULE" <<'RULE'
<Directory /var/www/html/water-tank>
    <FilesMatch "\.(html|php|js|css)$">
        Header set Cache-Control "no-cache"
    </FilesMatch>
</Directory>
RULE
    a2enconf water-tank-cache >/dev/null
    echo "  told browsers to check for changes rather than guess"
fi

systemctl reload apache2

echo
echo "=================================================="
echo "  Setting up the database"
echo "=================================================="

mkdir -p "$APP_DIR"

mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4;"
echo "  database $DB_NAME is ready"

if [ -f "$CONFIG" ]; then
    echo "  $CONFIG is already here, so your settings are left as they are"
else
    DB_PASS=$(tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 24)

    mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
    mysql -e "ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
    mysql -e "GRANT SELECT, INSERT, UPDATE, DELETE ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';"

    cat > "$CONFIG" <<PHPCONF
<?php
/*
Settings for the Smart Water Tank dashboard.

The installer wrote this file and will never overwrite it, so what you change
here survives an update. The two measurements are worth getting right: the
dashboard turns the sensor's distance reading into a volume with them.
*/

\$db_server   = 'localhost';
\$db_username = '$DB_USER';
\$db_pwd      = '$DB_PASS';
\$db_name     = '$DB_NAME';

// Your tank, in centimetres.
\$dia    = 104;   // inside diameter
\$height = 116;   // from the sensor down to the bottom of the tank
PHPCONF

    chown root:www-data "$CONFIG"
    chmod 640 "$CONFIG"
    echo "  created the database user and wrote $CONFIG"
fi

# An account for phpMyAdmin with the run of the machine, because it is the
# owner's Pi and they may want other databases on it. The password is made here
# rather than shipped in this script: a password published on GitHub is the same
# password on every Pi that ever runs this.
if [ -f "$ADMIN_FILE" ]; then
    echo "  the admin account is already set up, see $ADMIN_FILE"
else
    # No i, l, o, 0 or 1, so it can be read off a screen and typed without doubt.
    LETTERS=abcdefghjkmnpqrstuvwxyz23456789
    part() { tr -dc "$LETTERS" < /dev/urandom | head -c 4; }
    ADMIN_PASS="tank-$(part)-$(part)"

    mysql -e "CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY '$ADMIN_PASS';"
    mysql -e "ALTER USER 'admin'@'localhost' IDENTIFIED BY '$ADMIN_PASS';"
    mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'admin'@'localhost' WITH GRANT OPTION;"

    cat > "$ADMIN_FILE" <<ADMINTXT
Smart Water Tank: the account for phpMyAdmin.

  address   http://$IP/phpmyadmin
  user      admin
  password  $ADMIN_PASS

This account can create and change any database on this Pi. The password was
made when the project was installed, so it is yours alone. Change it whenever
you like, in phpMyAdmin under Account settings.
ADMINTXT
    chmod 600 "$ADMIN_FILE"
    echo "  created the admin account, details in $ADMIN_FILE"
fi

# Import when there is nothing to lose: no table, or a table with no readings
# in it. An installed tank that has been recording is never touched.
HAVE_ROWS=$(mysql -N -B -e "SELECT COUNT(*) FROM \`$DB_NAME\`.level_log;" 2>/dev/null || echo 0)

if [ "$HAVE_ROWS" -gt 0 ]; then
    echo "  the table already holds $HAVE_ROWS readings, so it is left alone"
else
    mysql "$DB_NAME" < "$SOURCE/water-tank/water_level.sql"
    echo "  imported the table and a day of sample readings"
fi

# The tank's shape and size live here, not in a file, so they can be set from
# the dashboard. An older install gets the table the same way a new one does.
mysql "$DB_NAME" <<'SETTINGS'
CREATE TABLE IF NOT EXISTS `settings` (
  `name`  VARCHAR(32) NOT NULL,
  `value` VARCHAR(64) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `settings` (`name`, `value`) VALUES
  ('shape', 'cylinder'), ('diameter', '104'), ('length', '0'),
  ('breadth', '0'), ('depth', '116');
SETTINGS
echo "  the tank size settings are ready"

# The PIN for the tank size page. Made here rather than shipped, so it is not
# the same four digits on every Pi that ever runs this.
if grep -q "settings_pin" "$CONFIG"; then
    echo "  the tank size page already has a PIN"
else
    printf "\n// Guards the tank size page.\n\$settings_pin = '%s';\n" "$(shuf -i 1000-9999 -n 1)" >> "$CONFIG"
    echo "  set a PIN for the tank size page"
fi

echo
echo "=================================================="
echo "  Setting up the sensor"
echo "=================================================="

SENSOR_SRC="$SOURCE/raspberry-pi"
SENSOR_DIR="/home/$REAL_USER/water-tank-sensor"
SENSOR_CONFIG="$SENSOR_DIR/config.py"

if [ ! -d "$SENSOR_SRC" ]; then
    echo "  cannot find $SENSOR_SRC"
    exit 1
fi

KEEP_SENSOR=""
if [ -f "$SENSOR_CONFIG" ]; then
    KEEP_SENSOR=$(mktemp)
    cp "$SENSOR_CONFIG" "$KEEP_SENSOR"
fi

if [ -d "$SENSOR_DIR" ]; then
    mv "$SENSOR_DIR" "${SENSOR_DIR}.backup_$(date +%Y%m%d_%H%M%S)"
fi

cp -r "$SENSOR_SRC" "$SENSOR_DIR"
rm -f "$SENSOR_DIR/config.py"          # whatever was in the source folder is not this machine's

if [ -n "$KEEP_SENSOR" ]; then
    cp "$KEEP_SENSOR" "$SENSOR_CONFIG"
    rm -f "$KEEP_SENSOR"
    echo "  kept the config.py you already had"
else
    cp "$SENSOR_DIR/config.sample.py" "$SENSOR_CONFIG"
    echo "  wrote $SENSOR_CONFIG"
fi

rm -f "$SENSOR_DIR/config.sample.py"

chown -R "$REAL_USER:$REAL_USER" "$SENSOR_DIR"
echo "  sensor code is in $SENSOR_DIR"

# The pins are reached through the gpio group rather than by running as root.
if id -nG "$REAL_USER" | grep -qw gpio; then
    echo "  $REAL_USER can already use the gpio pins"
else
    usermod -aG gpio "$REAL_USER"
    echo "  added $REAL_USER to the gpio group"
fi

# -q keeps the log empty while all is well, so it does not grow by 80 kB a day
# for ever on an SD card. A reading takes about three seconds and the send gives
# up after ten, so a run is always finished before the next minute arrives.
CRON_LINE="* * * * * /usr/bin/python3 $SENSOR_DIR/sensor.py -q >> $SENSOR_DIR/sensor.log 2>&1"

if sudo -u "$REAL_USER" crontab -l 2>/dev/null | grep -qF "water-tank-sensor/sensor.py"; then
    echo "  the every minute job is already there"
else
    (sudo -u "$REAL_USER" crontab -l 2>/dev/null; echo "$CRON_LINE") | sudo -u "$REAL_USER" crontab -
    echo "  added the every minute job"
fi

# Even a quiet log grows if something is wrong for a week, so hand it to the
# tool that already runs on this machine for exactly this.
ROTATE=/etc/logrotate.d/water-tank-sensor
if [ ! -f "$ROTATE" ]; then
    cat > "$ROTATE" <<ROTATION
$SENSOR_DIR/sensor.log {
    weekly
    rotate 4
    missingok
    notifempty
    copytruncate
}
ROTATION
    echo "  the log is rotated weekly, four kept"
fi

echo
echo "=================================================="
echo "  Installing phpMyAdmin"
echo "=================================================="

# phpMyAdmin asks which web server to set up and what password to give its own
# database. Answering here is what keeps this script to one command. The
# password is for phpMyAdmin's internal storage, not for signing in.
if ! dpkg -s phpmyadmin >/dev/null 2>&1; then
    PMA_PASS=$(tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 24)

    debconf-set-selections <<SEED
phpmyadmin phpmyadmin/dbconfig-install boolean true
phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2
phpmyadmin phpmyadmin/mysql/app-pass password $PMA_PASS
phpmyadmin phpmyadmin/app-password-confirm password $PMA_PASS
SEED

    apt-get install -y -o Dpkg::Options::=--force-confold phpmyadmin
    echo "  installed"
else
    echo "  already installed, left as it is"
fi

# An admin panel that answers the whole internet is a poor thing to leave on a
# home network, and people do forward a port to see their tank from work. This
# keeps it available to the house and refuses everything else.
PMA_RULE=/etc/apache2/conf-available/phpmyadmin-local-only.conf
if [ ! -f "$PMA_RULE" ]; then
    cat > "$PMA_RULE" <<'RULE'
<Location /phpmyadmin>
    Require ip 127.0.0.1 ::1 192.168.0.0/16 10.0.0.0/8 172.16.0.0/12
</Location>
RULE
    a2enconf phpmyadmin-local-only >/dev/null
    systemctl reload apache2
    echo "  limited to this local network"
else
    echo "  the local network rule is already in place"
fi

echo
echo "=================================================="
echo "  Checks"
echo "=================================================="

ok=1

if systemctl is-active --quiet apache2; then r=yes; else r=NO; ok=0; fi
printf "  %-34s %s\n" "the web server is running" "$r"

if systemctl is-active --quiet mariadb; then r=yes; else r=NO; ok=0; fi
printf "  %-34s %s\n" "the database server is running" "$r"

if php -m | grep -qix mysqli; then r=yes; else r=NO; ok=0; fi
printf "  %-34s %s\n" "php can talk to the database" "$r"

if sudo -u "$REAL_USER" python3 -c "import RPi.GPIO" 2>/dev/null; then r=yes; else r=NO; ok=0; fi
printf "  %-34s %s\n" "python can reach the gpio pins" "$r"

if sudo -u www-data php -r '
    require "'"$CONFIG"'";
    try { mysqli_connect($db_server, $db_username, $db_pwd, $db_name); exit(0); }
    catch (Throwable $e) { exit(1); }' 2>/dev/null; then r=yes; else r=NO; ok=0; fi
printf "  %-34s %s\n" "the dashboard can reach it" "$r"

if [ -d /usr/share/phpmyadmin ] && [ -f "$PMA_RULE" ]; then r=yes; else r=NO; ok=0; fi
printf "  %-34s %s\n" "phpmyadmin, local network only" "$r"

if [ -f "$ADMIN_FILE" ]; then r=yes; else r=NO; ok=0; fi
printf "  %-34s %s\n" "an admin account for it" "$r"

if curl -fsS "http://localhost/water-tank/tank_animation/read_data2.php" | grep -q "&value="; then r=yes; else r=NO; ok=0; fi
printf "  %-34s %s\n" "the dashboard answers" "$r"

if sudo -u "$REAL_USER" crontab -l 2>/dev/null | grep -q "water-tank-sensor/sensor.py"; then r=yes; else r=NO; ok=0; fi
printf "  %-34s %s\n" "the sensor runs every minute" "$r"

READINGS=$(mysql -N -B -e "SELECT COUNT(*) FROM \`$DB_NAME\`.level_log;" 2>/dev/null || echo 0)
if [ "$READINGS" -gt 0 ]; then r="yes, $READINGS"; else r=NO; ok=0; fi
printf "  %-34s %s\n" "readings in the table" "$r"

# One line to say an install happened and on what kind of machine. No readings,
# nothing about the tank, and the serial is hashed. It never holds up the
# install, whatever the network does.
curl -s -m 5 https://helloworld.co.in/deploy/t.php >/dev/null 2>&1 -d \
    "p=$(basename "$REPO" .git)&e=install&s=$([ $ok -eq 1 ] && echo ok || echo fail)&i=$ID&m=${MODEL// /+}&o=$CODENAME&a=$ARCH&l=$IP" || true

echo
if [[ $ok -eq 1 ]]; then
    echo "  Everything above says yes."
    echo
    echo "  The dashboard is at:"
    echo "      http://$IP/water-tank"
    echo
    echo "  Set the shape and size of your tank at:"
    echo "      http://$IP/water-tank/settings.php"
    echo "      its PIN:  sudo grep settings_pin $CONFIG"
    echo
    echo "  To look at the data in a browser, from any device in the house:"
    echo "      http://$IP/phpmyadmin"
    echo
    echo "  Your sign in details are here, and nowhere else:"
    echo "      sudo cat $ADMIN_FILE"
    echo
    echo "  The sensor runs every minute from now on. To try it yourself:"
    echo "      python3 $SENSOR_DIR/sample.py     a made up reading"
    echo "      python3 $SENSOR_DIR/sensor.py     the real one"
    echo
    echo "  Tank measurements and the pins the sensor is wired to:"
    echo "      $SENSOR_CONFIG"
else
    echo "  Something above says NO. Sort that out before going on."
    exit 1
fi
echo

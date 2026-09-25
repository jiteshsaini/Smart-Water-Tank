#!/bin/bash
#
# Runs sensor.py every minute through cron.
#
#   bash setup_cron.sh
#
# The installer does this for you. Use this script when the dashboard runs on
# another machine and you only want the sensor part on this Pi.

if [ -z "$BASH_VERSION" ]; then
    exec bash "$0" "$@"
fi

set -e

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCRIPT="$HERE/sensor.py"
LOG="$HERE/sensor.log"

# -q keeps the log empty while all is well. A reading takes about three
# seconds, and the send gives up after ten, so a run always finishes long
# before the next minute comes round.
LINE="* * * * * /usr/bin/python3 $SCRIPT -q >> $LOG 2>&1"

if [ ! -f "$SCRIPT" ]; then
    echo "cannot find $SCRIPT"
    exit 1
fi

if [ ! -f "$HERE/config.py" ]; then
    echo "there is no config.py yet. Copy config.sample.py to config.py and set"
    echo "the address of your dashboard in it, then run this again."
    exit 1
fi

if crontab -l 2>/dev/null | grep -qF "$SCRIPT"; then
    echo "already set up:"
    crontab -l | grep -F "$SCRIPT"
    exit 0
fi

(crontab -l 2>/dev/null; echo "$LINE") | crontab -

echo "added this line to your crontab:"
echo "  $LINE"
echo
echo "anything that goes wrong will appear in $LOG"

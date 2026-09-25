"""
Settings for the sensor.

Copy this file to config.py and adjust it. The installer writes config.py for
you and never overwrites it, so what you change here survives an update.
"""

# The machine running the dashboard.
#
# 127.0.0.1 means this Pi itself, which is what the installer sets up: the
# sensor and the dashboard on one machine.
#
# CHANGE THIS if the dashboard runs somewhere else, on a PC on your network or
# on a web host, and put that machine's address here instead:
#
#     SERVER_IP = "192.168.1.50"
#     SERVER_IP = "yourdomain.com"
#
SERVER_IP = "127.0.0.1"

# Where the dashboard sits on that machine. Only worth changing if you put the
# water-tank folder somewhere other than the top of the web root.
SERVER_PATH = "/water-tank/insert_data.php"

SERVER_URL = "http://" + SERVER_IP + SERVER_PATH

# The pins the HC-SR04 is wired to, in BCM numbering.
TRIG = 23
ECHO = 24

# An LED or buzzer that comes on when the tank is nearly empty. Set ALARM to a
# pin number to use one, or leave it as None.
#
# ALARM_DISTANCE is measured the way the sensor measures: from the sensor down
# to the water. A bigger number means less water left. For a tank 116 cm deep,
# 76 means the alarm sounds with about 40 cm of water in it.
#
# The size of your tank is not set here. The sensor only measures a distance;
# the dashboard is what turns that into litres, and it holds the measurements.
ALARM = None
ALARM_DISTANCE = 76

"""
Sends a made up reading, to check the Pi can reach the dashboard.

    python3 sample.py

Use this before wiring the sensor. If a number appears on the dashboard, the
network, the web server and the database are all working, and anything that
goes wrong afterwards is the sensor or its wiring.
"""

import random
import sys
import urllib.error

import sensor


def main():
    distance = round(random.uniform(20, 100), 3)
    print("pretending the sensor read %s cm" % distance)

    try:
        print(sensor.send(distance))
    except (urllib.error.URLError, OSError) as problem:
        print("could not reach %s: %s" % (sensor.config.SERVER_URL, problem), file=sys.stderr)
        return 1

    return 0


if __name__ == "__main__":
    sys.exit(main())

"""
Reads the water level with an HC-SR04 and sends it to the dashboard.

Run it once to see what it measures:

    python3 sensor.py
    python3 sensor.py -q      only complain if something is wrong

Then let cron run it every minute. setup_cron.sh sets that up, and so does the
installer.

The sensor measures the distance from itself down to the surface of the water,
so a full tank reads small and an empty one reads large. The dashboard turns
that into a depth and a volume.
"""

import argparse
import statistics
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

import RPi.GPIO as GPIO

import config

quiet = False       # cron sets this, so an idle log stays empty

SPEED = 17150       # half the speed of sound, in cm per second
SAMPLES = 20        # readings per run, of which the middle one is used
TIMEOUT = 0.06      # seconds to wait for an echo, about 10 metres


def say(message):
    if not quiet:
        print(message)


def setup():
    GPIO.setwarnings(False)
    GPIO.setmode(GPIO.BCM)
    GPIO.setup(config.TRIG, GPIO.OUT)
    GPIO.setup(config.ECHO, GPIO.IN)
    GPIO.output(config.TRIG, False)

    if config.ALARM is not None:
        GPIO.setup(config.ALARM, GPIO.OUT)
        GPIO.output(config.ALARM, True)     # off, for the usual wiring

    time.sleep(0.3)                          # let the sensor settle


def measure_once():
    """
    One distance in centimetres, or None if the sensor did not answer.

    Both waits below are given a deadline. Without one, a loose wire or a
    sensor that never echoes leaves this loop spinning for ever, and a minute
    later cron starts another copy that does the same.
    """
    GPIO.output(config.TRIG, True)
    time.sleep(0.00001)
    GPIO.output(config.TRIG, False)

    deadline = time.monotonic() + TIMEOUT
    while GPIO.input(config.ECHO) == 0:
        if time.monotonic() > deadline:
            return None
    started = time.monotonic()

    deadline = started + TIMEOUT
    while GPIO.input(config.ECHO) == 1:
        if time.monotonic() > deadline:
            return None
    ended = time.monotonic()

    return round((ended - started) * SPEED, 3)


def measure():
    """
    The middle reading of several.

    Ultrasonic sensors misread now and then, usually far too long when an echo
    is missed. The middle value ignores those, where an average would be
    dragged along by them.
    """
    readings = []

    for _ in range(SAMPLES):
        distance = measure_once()
        if distance is not None and 2 <= distance <= 400:
            readings.append(distance)
        time.sleep(0.06)

    if not readings:
        return None
    return round(statistics.median(readings), 3)


def send(distance):
    """Hand the reading to the dashboard."""
    url = config.SERVER_URL + "?" + urllib.parse.urlencode({"dist": distance})
    request = urllib.request.Request(url, headers={"User-Agent": "water-tank-sensor/1"})

    with urllib.request.urlopen(request, timeout=10) as answer:
        return answer.read().decode().strip()


def warn_if_low(distance):
    """
    The further the water is from the sensor, the less of it there is.

    This works in the distance the sensor actually measures, so the sensor
    needs to know nothing about the size of the tank. The measurements live on
    the dashboard, which is where litres are worked out, and keeping them in
    one place means they cannot drift apart.
    """
    if config.ALARM is None:
        return

    low = distance >= config.ALARM_DISTANCE
    GPIO.output(config.ALARM, not low)      # the usual wiring: off is high
    say("alarm %s" % ("on, the tank is low" if low else "off"))


def main():
    global quiet

    parser = argparse.ArgumentParser(description="Measure the water level and send it to the dashboard.")
    parser.add_argument("-q", "--quiet", action="store_true",
                        help="say nothing unless something goes wrong, which is what cron wants")
    quiet = parser.parse_args().quiet

    setup()

    distance = measure()
    if distance is None:
        print("the sensor did not answer, check the wiring", file=sys.stderr)
        return 1

    say("distance: %s cm" % distance)

    try:
        say(send(distance))
    except (urllib.error.URLError, OSError) as problem:
        print("could not reach %s: %s" % (config.SERVER_URL, problem), file=sys.stderr)
        return 1

    warn_if_low(distance)
    return 0


if __name__ == "__main__":
    sys.exit(main())

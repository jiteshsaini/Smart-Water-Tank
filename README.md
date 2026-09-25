# Smart Water Tank

<p align="left">
Live demo:
<a href='https://helloworld.co.in/demo/water-tank/' target='_blank'>
   <img src='https://raw.githubusercontent.com/jiteshsaini/files/main/img/logo3.gif' height='40px'>
</a>
Watch the video:
<a href='https://youtu.be/7uLuwq3Zd_M' target='_blank'>
   <img src='https://raw.githubusercontent.com/jiteshsaini/files/main/img/btn_youtube.png' height='40px'>
</a>
Read the article:
<a href='https://helloworld.co.in/article/smart-water-tank/' target='_blank'>
   <img src='https://raw.githubusercontent.com/jiteshsaini/files/main/img/logo3.gif' height='40px'>
</a>
</p>

An ultrasonic sensor sits on top of your water tank and measures how far down
the water is. A Raspberry Pi reads it every minute and a web page shows the
level, as an animated tank and as a graph of the day. Open it from any phone or
laptop in the house.

<p align="center">
   <img src="https://helloworld.co.in//wp-content/uploads/images_home/watertank_overview.gif">
</p>

**Updated for the current Raspberry Pi OS (Trixie, Debian 13), September 2026.**
One command installs everything.

## What you will need

* A Raspberry Pi with GPIO pins. Any of them will do; this was last tested on a
  Pi 3 Model A Plus with 512 MB of memory, which is the smallest thing likely to
  be lying around.
* An HC-SR04 ultrasonic sensor, about a pound or a dollar.
* Four jumper wires, and two resistors for the echo line (see Wiring).
* Raspberry Pi OS, and a network the Pi can reach.

## Which part runs where

This is worth two minutes before you start, because almost everything that goes
wrong is a misunderstanding of it.

```
   HC-SR04  ── GPIO 23 and 24 ──┐
                                │
                        [ Raspberry Pi ]
                                │
     sensor.py  ── every minute ──▶  insert_data.php
                                            │
                                    water_level, in MariaDB
                                            │
                                     the dashboard  ──▶  any browser in the house
```

Two halves, and by default both sit on the same Pi:

* **`raspberry-pi/`** is the sensor. It measures a distance and sends it. It
  knows nothing about tanks, litres or web pages.
* **`water-tank/`** is the dashboard. It stores readings, works out the volume
  and draws the page. It never talks to the sensor; it only reads what arrived.

They meet at one address, `insert_data.php`, which takes a distance in
centimetres. That is the whole interface between them, which is why the
dashboard can equally well live on another machine.

## Install

Two commands on the Pi:

```bash
curl -fsSL https://raw.githubusercontent.com/jiteshsaini/Smart-Water-Tank/master/setup_water_tank.sh -o setup_water_tank.sh
```

```bash
sudo bash setup_water_tank.sh
```

Downloading it first, rather than piping it into `sudo bash`, lets you read the
script before running it as root. It fetches the rest of the project itself.

It asks nothing except your password. It will:

* install Apache, PHP, MariaDB and phpMyAdmin
* install `python3-rpi-lgpio`, which is what makes `RPi.GPIO` work on a current
  Pi OS. Without it the sensor code fails, and this is the single reason older
  copies of this project stopped working
* create the `water_level` database and a database account of its own, rather
  than using root
* put the dashboard in `/var/www/html/water-tank` and the sensor in
  `~/water-tank-sensor`
* import a day of real readings, dated 1 Sep 2026, so the graph has something
  to show before your own sensor has recorded anything
* add a cron job that runs the sensor every minute
* print the addresses to open, and where to find your PIN

Run it again whenever you update the code. It fetches the latest version, keeps
your settings, your readings and your PIN, and moves the previous copy aside
rather than overwriting it.

## Wiring

| HC-SR04 | Raspberry Pi |
|---|---|
| VCC | 5V |
| GND | Ground |
| TRIG | GPIO 23 |
| ECHO | GPIO 24, through a voltage divider |

The echo pin puts out 5 V and the Pi's pins expect 3.3 V, so echo goes through
two resistors, 1 kΩ from echo to the Pi pin and 2 kΩ from that pin to ground.
Anything in that ratio works. Connecting echo directly is the usual way to
damage a Pi with this sensor.

Different pins? Change them in `~/water-tank-sensor/config.py`.

## First run

**Check the path before the hardware.** This sends a made up reading, so if a
number appears on the dashboard, the network, the web server and the database
are all fine:

```bash
python3 ~/water-tank-sensor/sample.py
```

**Then the sensor itself:**

```bash
python3 ~/water-tank-sensor/sensor.py
```

It prints the distance it measured and what the server said. From then on cron
runs it every minute, quietly.

**Then open the dashboard**, at the address the installer printed:

```
http://<your pi>/water-tank
```

## Setting your tank

Follow the **Tank size** link on the dashboard. It asks for the PIN the
installer made:

```bash
sudo grep settings_pin /var/www/html/water-tank/config.php
```

Choose **round** or **rectangular**, give the measurements, and the page tells
you what a full tank comes to. Everything follows from that: the litres, the
scale on the dashboard, and the shape of the tank that is drawn.

Measure the inside of the tank, and measure depth from **the sensor** down to
the bottom, not from the rim.

## The dashboard on another machine

The sensor and the dashboard only meet at one address, so the dashboard can run
on a PC on your network, or on a web host, with the sensor still on the Pi.

On the Pi, copy the `raspberry-pi` folder across, then:

```bash
cp config.sample.py config.py
```

Change one line in it:

```python
SERVER_IP = "192.168.1.50"     # or yourdomain.com
```

and add the cron job:

```bash
bash setup_cron.sh
```

On the other machine, put the `water-tank` folder in the web root, create a
database called `water_level`, import `water-tank/water_level.sql`, and copy
`config.sample.php` to `config.php` with your database details in it. Do not
run the installer there; it is written for a Pi.

## What each file is

**`water-tank/`, the dashboard**

| File | What it does |
|---|---|
| `index.html` | The page, three panels in frames |
| `insert_data.php` | Takes a reading from the sensor. The only way in |
| `settings.php` | Shape and size of your tank, behind a PIN |
| `util.php` | Database connection, the settings, and the volume arithmetic |
| `config.php` | Your database details and PIN. Written by the installer |
| `basic_page/` | The panel with the last reading and how old it is |
| `tank_animation/` | The animated tank |
| `tank_simulation/` | The same tank fed by the server clock, to play with |
| `graph/` | The day's readings, drawn with PHPlot |
| `water_level.sql` | The table, and a day of real readings |

**`raspberry-pi/`, the sensor**

| File | What it does |
|---|---|
| `sensor.py` | Measures and sends. This is what cron runs |
| `sample.py` | Sends a made up reading, to test the path without hardware |
| `config.py` | Where to send, which pins, the optional alarm |
| `setup_cron.sh` | Adds the cron job, for the two machine setup |

## When it does not work

| What you see | Usually |
|---|---|
| "the sensor did not answer" | Wiring. Check TRIG and ECHO, and that the sensor has 5 V |
| Readings arrive but are wildly wrong | The sensor is not square to the water, or is seeing the side of the tank |
| "could not reach ..." | `SERVER_IP` in `config.py`, or the web server is not running |
| The dashboard shows an old reading | Cron is not running the sensor. `crontab -l` should list it |
| Litres look wrong | The measurements on the Tank size page, in particular depth measured from the rim instead of the sensor |
| The page still looks the same after you edit a file | Your browser is holding the old copy. Reload with Ctrl and Shift held down |

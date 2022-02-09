# Smart Water Tank with online monitoring dashboard (Web GUI)
<p align="left">
Live Demo:- <a href='https://helloworld.co.in/water-tank' target='_blank'>
   <img src='https://github.com/jiteshsaini/files/blob/main/img/logo3.gif' height='40px'>
</a> Watch the video on Yotube: 
<a href='https://youtu.be/7uLuwq3Zd_M' target='_blank'>
   <img src='https://github.com/jiteshsaini/files/blob/main/img/btn_youtube.png' height='40px'>
</a>
</p>

This is an IOT project implemented using Raspberry Pi and custom Web Application. 

As shown in the animation below, an ultrasonic sensor is fitted on top of the water tank. The sensor is connected to Raspberry Pi, which measures water level in the tank every minute. The water level reading is fed to a remote database. A Web Application running in the remote host makes use of this data to update the water level animation and the thin bar line graph.

<p align="center">
   <img src="https://helloworld.co.in/custom_php/img/watertank_overview.gif">
</p>

## How to use the code

**web_host**
1. This directory contains the code of Dashboard (Web GUI). It is required to be placed inside the public directory ("htdocs" or "www") of your webserver.
You can install a Webserver such as XAMPP on your PC or Laptop and place 'web_host' directory in 'htdocs' folder.<br>

2. Create a database with name 'water_level' using phpmyadmin utility and import the "water_level.sql" database file (present in 'web_host' directory). <br>

3. Open browser and go to this URL "http://localhost/web_host". You should see the dashboard<br>

**Raspberry Pi**

1. This directory contains a Python file 'sensor.py'. It is required to be placed inside Raspberry Pi at any location.<br> Make sure that Raspberry Pi and Webserver are connected to same wifi LAN (for this demo code).<br>

2. Edit the Web Server IP address in line 89 of 'sensor.py' as per your device.<br>

3. Create a cron job which runs the file 'sensor.py' every minute. So that the reading is sent to the Web server every minute.<br>


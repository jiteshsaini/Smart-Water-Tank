#Project: Smart Water Tank
#Created by: Jitesh Saini

#this script should be executed every minute through CRON settings of Raspberry Pi

import RPi.GPIO as GPIO
import time,os

import datetime
from urllib import urlopen

TRIG = 6
ECHO = 5
ALARM = 23

GPIO.setmode(GPIO.BCM)

GPIO.setup(TRIG,GPIO.OUT)
GPIO.setup(ECHO,GPIO.IN)
GPIO.output(TRIG, False)

GPIO.setup(ALARM,GPIO.OUT)
GPIO.output(ALARM, True)

print ("Waiting For Sensor To Settle")
time.sleep(1) #settling time 

def get_distance():
	dist_add = 0
	k=0
	for x in range(20):
		try:
			GPIO.output(TRIG, True)
			time.sleep(0.00001)
			GPIO.output(TRIG, False)

			while GPIO.input(ECHO)==0:
				pulse_start = time.time()

			while GPIO.input(ECHO)==1:
				pulse_end = time.time()

			pulse_duration = pulse_end - pulse_start
			
			distance = pulse_duration * 17150

			distance = round(distance, 3)
			print (x, "distance: ", distance)
		
			if(distance > 125):# ignore erroneous readings (max distance cannot be more than 125)
				k=k+1
				continue
		
			dist_add = dist_add + distance
			#print "dist_add: ", dist_add
			time.sleep(.1) # 100ms interval between readings
		
		except Exception as e: 
		
			pass
	
	
	print ("x: ", x+1)
	print ("k: ", k)

	avg_dist=dist_add/(x+1 -k)
	dist=round(avg_dist,3)
	#print ("dist: ", dist)
	return dist

def sendData_to_remoteServer(url,dist):
	url_full=url+str(dist)
	urlopen(url_full)
	print("sent to url: ",url_full)
	
def low_level_warning(dist):
	level=114-dist
	if(level<40):
		print("level low : ", level)
		GPIO.output(ALARM, False)
	else:
		GPIO.output(ALARM, True)
		print("level ok")
		
passcode="xyz"

#Assuming web server is running on IP Address: 192.168.1.3
url_remote="http://192.168.1.3/web_host/watertank/insert_data.php?passcode=" + passcode + "&level="

distance=get_distance()

print ("distance: ", distance)

sendData_to_remoteServer(url_remote,distance)

low_level_warning(distance)

print ("---------------------")
	
			

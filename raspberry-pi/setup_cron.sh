#!/bin/bash

# specify the file name that you want to run through cron automatically every one minute
FILE_NAME="sensor.py"

crontab -u $USER -l| grep $PWD/$FILE_NAME > /dev/null
if [ $? -eq 0 ]
then
	echo "$FILE_NAME is already running"
else
	crontab -l > mycron
	echo "* * * * * sudo python $PWD/$FILE_NAME &" >> mycron
	crontab mycron
	rm mycron
	echo "cron task added. Now $PWD/$FILE_NAME file will run every minute"
fi

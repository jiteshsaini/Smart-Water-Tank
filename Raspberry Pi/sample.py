import random, os

def measure_distance():
	d=random.randrange(5, 100)
	return d

def sendData_to_remoteServer(dist):
	url_remote="http://192.168.1.5/water-tank/insert_data.php?dist=" + str(dist)
	cmd="curl -s " + url_remote
	result=os.popen(cmd).read()
	print (cmd)
	
def main():
	
	dist = measure_distance()
	sendData_to_remoteServer(dist)
	
	
if __name__ == '__main__':
    main()

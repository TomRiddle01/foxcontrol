#!/bin/sh

case "$1" in
    start)
	
	cd /home/foxcontrol/FoxControl
	php control.php TM2 </dev/null >FoxControl.log 2>&1 &
	
	echo $! > /var/run/FoxControl.pid
	echo FoxControl started
    ;;
    
	stop)

	kill -TERM `cat /var/run/FoxControl.pid`
	echo FoxControl stopped
    ;;
esac

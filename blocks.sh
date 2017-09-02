#!/bin/bash
c=11660
while [ $c -le 269070 ]
do
/usr/bin/php /root/notify/esnotify/blockFinder.php "ion" "$c"
	(( c++ ))
done

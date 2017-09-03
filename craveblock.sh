#!/bin/sh
#curl "http://crave.masternodes.pro/coin/block" -d "$@"
/usr/bin/php /root/notify/esnotify/blockFound.php "crave" "$@" >> crave.blockfound.log

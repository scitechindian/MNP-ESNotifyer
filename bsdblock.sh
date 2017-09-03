#!/bin/sh
#curl "http://bsd.masternodes.pro/coin/block" -d "$@"@"
/usr/bin/php /root/notify/esnotify/blockFound.php "bsd" "$@" >> bsd.blockfound.log
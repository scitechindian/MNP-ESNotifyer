#!/bin/sh
#curl "http://rns.masternodes.pro/coin/block" -d "$@"
/usr/bin/php /root/notify/esnotify/blockFound.php "rns" "$@" >> rns.blockfound.log
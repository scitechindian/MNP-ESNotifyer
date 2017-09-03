#!/bin/sh
#curl "http://ntrn.masternodes.pro/coin/block" -d "$@"
/usr/bin/php /root/notify/esnotify/blockFound.php "ntrn" "$@" >> ntrn.blockfound.log

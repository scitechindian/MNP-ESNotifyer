#!/bin/sh
#curl "http://sib.masternodes.pro/coin/block" -d "$@"
/usr/bin/php /root/notify/esnotify/blockFound.php "sib" "$@" >> sib.blockfound.log
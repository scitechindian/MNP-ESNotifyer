#!/bin/sh
#curl "http://excl.masternodes.pro/coin/block" -d "$@"
/usr/bin/php /root/notify/esnotify/blockFound.php "excl" "$@" >> excl.blockfound.log

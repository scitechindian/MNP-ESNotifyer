#!/bin/sh
#curl "http://arc.masternodes.pro/coin/block" -d "$@"
/usr/bin/php /root/notify/esnotify/blockFound.php "arc" "$@" >> arc.blockfound.log

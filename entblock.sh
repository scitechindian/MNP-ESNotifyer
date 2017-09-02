#!/bin/sh
#curl "http://ent.masternodes.pro/coin/block" -d "$@"
/usr/bin/php /root/notify/esnotify/blockFound.php "ent" "$@" >> ent.blockfound.log


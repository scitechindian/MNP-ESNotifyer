#!/bin/sh
#curl "http://pivx.masternodes.pro/coin/block" -d "$@"
/usr/bin/php /root/notify/esnotify/blockFound.php "pivx" "$@" >> pivx.blockfound.log

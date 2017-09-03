#!/bin/sh
#curl "http://chc.masternodes.pro/chc/block" -d "$@"
/usr/bin/php /root/notify/esnotify/blockFound.php "chc" "$@" >> chc.blockfound.log
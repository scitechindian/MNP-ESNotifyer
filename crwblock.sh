#!/bin/sh
#curl "http://crw.masternodes.pro/coin/block" -d "$@"
/usr/bin/php /root/notify/esnotify/blockFound.php "crw" "$@" >> crw.blockfound.log
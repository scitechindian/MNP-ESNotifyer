#!/bin/sh
#curl "http://synx.masternodes.pro/coin/block" -d "$@"@"
/usr/bin/php /root/notify/esnotify/blockFound.php "synx" "$@" >> synx.blockfound.log
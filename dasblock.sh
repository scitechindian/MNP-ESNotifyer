#!/bin/sh
#curl "http://das.masternodes.pro/coin/block" -d "$@"@"
/usr/bin/php /root/notify/esnotify/blockFound.php "das" "$@" >> das.blockfound.log
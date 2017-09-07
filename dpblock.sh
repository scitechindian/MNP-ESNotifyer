#!/bin/sh
#curl "http://crm.masternodes.pro/coin/block" -d "$@"
/usr/bin/php /root/notify/esnotify/blockFound.php "dp" "$@" >> dp.blockfound.log
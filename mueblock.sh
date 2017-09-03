#!/bin/sh
#curl "http://mue.masternodes.pro/chc/block" -d "$@"@"
/usr/bin/php /root/notify/esnotify/blockFound.php "mue" "$@" >> mue.blockfound.log
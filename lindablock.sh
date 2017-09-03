#!/bin/sh
#curl "http://linda.masternodes.pro/chc/block" -d "$@"@"
/usr/bin/php /root/notify/esnotify/blockFound.php "linda" "$@" >> linda.blockfound.log
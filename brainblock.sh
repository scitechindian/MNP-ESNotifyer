#!/bin/sh
#curl "http://brain.masternodes.pro/coin/block" -d "$@"@"
/usr/bin/php /root/notify/esnotify/blockFound.php "brain" "$@" >> brain.blockfound.log
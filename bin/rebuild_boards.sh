#!/bin/sh

sleep 4

for board in `cat /www/global/yotsuba/boardlist.txt`;
do
        mkdir -p /www/4chan.org/web/boards/$board/res

        pushd /www/4chan.org/web/boards/$board
        echo $board >> /www/perhost/startup.txt
        /usr/local/bin/php -d display_startup_errors=on -d display_errors=on -d log_errors=off /www/global/yotsuba/imgboard.php rebuildall >>/www/perhost/startup.txt 2>&1
        popd

        chown -R www:www /www/4chan.org/web/boards/$board
        chmod -R g+w /www/4chan.org/web/boards/$board
done

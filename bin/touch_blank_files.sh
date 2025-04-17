#!/usr/local/bin/bash

for board in `cat /www/global/yotsuba/boardlist.txt`;

do
    touch /www/4chan.org/web/boards/$board/imgboard.html >>/dev/null  2>&1
    i="1"
    while [ $i -lt 16 ]
    do
        touch /www/4chan.org/web/boards/$board/$i.html >>/dev/null 2>&1
        let i=i+1
    done

        chown -R www:www /www/4chan.org/web/boards/$board
        chmod -R g+w /www/4chan.org/web/boards/$board
done

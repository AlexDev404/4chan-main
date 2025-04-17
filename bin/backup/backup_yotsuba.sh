#!/bin/sh

tar -c --one-file-system --exclude tarsnap-cache --exclude dcc/log --exclude www/logs --exclude nginx/logs -zpf /backup/yotsuba/yotsuba_daily.tgz /etc /usr/local /home /root /boot/loader.conf /usr/src/sys/amd64/conf /www/conf /www/4chan.org/web/www /www/4chan.org/web/team /www/4chan.org/web/reports /www/git /www/global
su -m global -c '/usr/bin/scp -P 914 /backup/yotsuba/yotsuba_daily.tgz miura.int:/backup/drop/yotsuba_daily.tgz'

#!/bin/sh

tar -c --one-file-system --exclude tarsnap-cache --exclude www/logs --exclude nginx/logs -zpf /var/tmp/ayase_daily.tgz /etc /usr/local /home /root /boot/loader.conf /usr/src/sys/amd64/conf /www/4chan.org/web/sys /www/conf /www/keys
su -m global -c '/usr/bin/scp -P 914 /var/tmp/ayase_daily.tgz miura.int:/backup/drop/ayase_daily.tgz'

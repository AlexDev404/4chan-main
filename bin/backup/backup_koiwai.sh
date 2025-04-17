#!/bin/sh

nice -n 5 tar -c --one-file-system --exclude tarsnap-cache --exclude www/logs --exclude nginx/logs -zpf /var/tmp/koiwai_daily.tgz /etc /usr/local /home /root /boot/loader.conf /usr/src/sys/amd64/conf /www/conf
su -m global -c '/usr/bin/scp -P 914 /var/tmp/koiwai_daily.tgz miura.int:/backup/drop/koiwai_daily.tgz'

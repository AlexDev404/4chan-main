#!/bin/sh

nice -n 5 tar -c --one-file-system --exclude tarsnap-cache --exclude www/logs --exclude nginx/logs -zpf /backup/danbo/danbo_daily.tgz /etc /usr/local /var/db /home /root /boot/loader.conf /usr/src/sys/amd64/conf /www/conf
su -m global -c '/usr/bin/scp -P 914 /backup/danbo/danbo_daily.tgz miura.int:/backup/drop/danbo_daily.tgz'

#!/bin/sh

nice -n 5 tar -c --one-file-system -zpf /backup/miura/miura_daily.tgz /etc /usr/local/etc /usr/local/src /usr/local/mysql/my.cnf /home /root /boot/loader.conf /usr/src/sys/amd64/conf /backup/mysql/pass_users.sql

# cron for weekly & monthly (and same for copied-in files)

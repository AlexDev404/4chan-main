#!/bin/sh

cp /backup/ayase/ayase_daily.tgz /backup/ayase/ayase_weekly.tgz
cp /backup/danbo/danbo_daily.tgz /backup/danbo/danbo_weekly.tgz
cp /backup/koiwai/koiwai_daily.tgz /backup/koiwai/koiwai_weekly.tgz
cp /backup/yotsuba/yotsuba_daily.tgz /backup/yotsuba/yotsuba_weekly.tgz
cp /backup/miura/miura_daily.tgz /backup/miura/miura_weekly.tgz

#mv /backup/mysql/weekly /backup/mysql/weekly_temp || true
#cp -R /backup/mysql/daily /backup/mysql/weekly && rm -rf /backup/mysql/weekly_temp

#tarsnap -d -f miura_mysql_backup_weekly || true
#tarsnap -d -f miura_mysql_backup_weekly || true
#tarsnap -c -f miura_mysql_backup_weekly /backup/mysql/weekly

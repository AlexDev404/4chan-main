#!/usr/local/bin/bash

# Backup MariaDB tables LOCALLY
rm -rf /backup/mysql/daily_temp || exit 1
innobackupex --defaults-file=/usr/local/mysql/my.cnf --export --no-timestamp --user=root --password=`cat /root/dbpasswd/root` /backup/mysql/daily_temp || exit
#rm -rf /backup/mysql/daily_temp/backend/{keyword_log,user_actions,xff}.ibd
rm -rf /backup/mysql/daily && mv /backup/mysql/daily_temp /backup/mysql/daily
/usr/local/mariadb55/bin/mysqldump --no-data -ubackend_admin -p`cat /root/dbpasswd/backend` -hdb1.int backend > /backup/mysql/backend_schema.sql
#sleep 1800 && /usr/local/etc/rc.d/mysql-server onerestart

# Replicate backups to THE CLOUD (tm)
tarsnap -d -f miura_mysql_backup_daily.part || true
tarsnap -d -f miura_mysql_backup_daily || true
tarsnap -c -f miura_mysql_backup_daily /backup/mysql/daily

# Replicate backups to danbo
tar cfz /backup/mysql/daily.tgz /backup/mysql/daily
su -m global -c '/usr/bin/scp -Crp -P 914 /backup/mysql/daily.tgz /backup/mysql/backend_schema.sql danbo.int:/backup/drop/'
#TEMP not deleting the file here
#rm /backup/mysql/daily.tgz

# TEMP restart mysql
#/usr/local/etc/rc.d/mysql-server restart

#!/usr/local/bin/bash

/usr/local/mariadb55/bin/mysqldump -ubackend_admin -p`cat /root/dbpasswd/backend` -hdb1.int backend pass_users > /backup/mysql/pass_users.sql

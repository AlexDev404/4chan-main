#!/usr/local/bin/bash

[ -e /backup/drop/backend_schema.sql ] && mv /backup/drop/backend_schema.sql /backup/mysql/daily/
[ -e /backup/drop/daily.tgz ] && mv /backup/drop/daily.tgz /backup/mysql/daily/

chown root:wheel /backup/mysql/daily/daily.tgz /backup/mysql/daily/backend_schema.sql

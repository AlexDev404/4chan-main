#!/bin/sh

unset GIT_DIR
cd /www/4chan.org/web/team
git pull
cd /www/4chan.org/web/www
git pull
cd /www/global/yotsuba
git pull
cd /www/4chan.org/web/reports
git pull
cd /www/global/static
git pull
export PATH=/sbin:/bin:/usr/sbin:/usr/bin:/usr/local/sbin:/usr/local/bin
/usr/local/bin/suid_run_global resync_global >/dev/null 2>&1
#nohup sh -c "(sleep 30; wget --tries=1 --timeout=60 --no-check-certificate -O/dev/null -U- https://10.0.0.19/test/imgboard.php?mode=rebuildall >/dev/null 2>&1)" &

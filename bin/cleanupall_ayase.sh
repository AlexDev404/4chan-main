#!/usr/local/bin/bash

for b in `cat /www/global/yotsuba/boardlist.txt`; do cd /www/4chan.org/web/boards/$b; echo $b; php /www/global/yotsuba/admin.php cleanup; done



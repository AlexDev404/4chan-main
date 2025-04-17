#!/bin/sh

for board in `cat /www/global/yotsuba/boardlist.txt` test; do
echo -- $board
mysql -uimg_admin -pPASSWORD_HERE -hdb1.int -e "alter table \`$board\` CHANGE \`ext\` \`ext\` CHAR(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;" img1
done

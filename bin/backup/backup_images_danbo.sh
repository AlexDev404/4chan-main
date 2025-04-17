#!/bin/sh
#
# Modified ZFS Backup Script
## Original Source: http://www.aisecure.net/2012/01/11/automated-zfs-incremental-backups-over-ssh/
#
# If backup ever starts failing, remove all OLD snapshots. (zfs list -t snapshot and zfs destroy FULL@SNAPSHOT
# Run backup script then run zfs send -R ssd@backup-YEAR-MO-DA | zfs receive -Fduv backup/ssd
# After that the backups will run normally
 
pool="ssd"
destination="backup/ssd"
type="backup"

today=`date +"$type-%Y-%m-%d"`
yesterday=`date -v -1d +"$type-%Y-%m-%d"`
daybeforeyday=`date -v -2d +"$type-%Y-%m-%d"`

# create today snapshot
snapshot_today="$pool@$today"
# look for a snapshot with this name
if zfs list -H -o name -t snapshot | sort | grep "$snapshot_today$" > /dev/null; then
echo "snapshot $snapshot_today already exists"
exit 1
else
echo "taking today's snapshot $snapshot_today"
zfs snapshot -r $snapshot_today
fi

# look for yesterday snapshot
snapshot_yesterday="$pool@$yesterday"
snapshot_daybeforeyesterday="$pool@$daybeforeyday"
if zfs list -H -o name -t snapshot | sort | grep "$snapshot_yesterday$" > /dev/null; then
echo "yesterday snapshot $snapshot_yesterday exists, let's proceed with backup"

zfs send -R -i $snapshot_yesterday $snapshot_today | zfs receive -Fduv $destination

echo "backup complete, destroying day before yesterday snapshot"
zfs destroy -r $snapshot_daybeforeyesterday
exit 0
else
echo "missing yesterday snapshot $snapshot_yesterday"
exit 1
fi


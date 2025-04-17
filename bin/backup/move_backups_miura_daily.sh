#!/usr/local/bin/bash

[ -e /backup/drop/ayase_daily.tgz ] && mv /backup/drop/ayase_daily.tgz /backup/ayase/ayase_daily.tgz
[ -e /backup/drop/danbo_daily.tgz ] && mv /backup/drop/danbo_daily.tgz /backup/danbo/danbo_daily.tgz
[ -e /backup/drop/koiwai_daily.tgz ] && mv /backup/drop/koiwai_daily.tgz /backup/koiwai/koiwai_daily.tgz
[ -e /backup/drop/yotsuba_daily.tgz ] && mv /backup/drop/yotsuba_daily.tgz /backup/yotsuba/yotsuba_daily.tgz

chown root:wheel /backup/ayase/ayase_daily.tgz /backup/danbo/danbo_daily.tgz /backup/koiwai/koiwai_daily.tgz /backup/yotsuba/yotsuba_daily.tgz

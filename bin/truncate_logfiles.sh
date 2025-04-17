#!/usr/local/bin/bash

for f in /www/perhost/*log; do /usr/bin/truncate -c -s 0 "$f"; done

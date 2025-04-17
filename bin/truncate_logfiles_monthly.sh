#!/usr/local/bin/bash

for f in /www/perhost/*txt /var/log/exim/*; do /usr/bin/truncate -c -s 0 "$f"; done

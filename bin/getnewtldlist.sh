#!/usr/local/bin/bash

cd /usr/local/etc/exim

wget -N -q --no-check-certificate https://publicsuffix.org/list/effective_tld_names.dat

chown mailnull:mail effective_tld_names.dat
chmod 644 effective_tld_names.dat

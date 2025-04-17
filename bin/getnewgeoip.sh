#!/usr/local/bin/bash

cd /usr/local/share/GeoIP/

wget -N -q http://geolite.maxmind.com/download/geoip/database/GeoLiteCountry/GeoIP.dat.gz
wget -N -q http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz

gzip -df GeoIP.dat.gz
gzip -df GeoLiteCity.dat.gz

mv GeoLiteCity.dat GeoIPCity.dat

#!/bin/ash

# Check env variables
## MYSQL_ROOT_PASSWORD is no more mandatory
if [ -z "${MYSQL_HOST}" ]
then
	echo 'MYSQL_HOST must be set'
	exit 1
fi
if [ -z "${LANG}" ]
then
    LANG='fr_FR'
fi
if [ -z "${TZ}" ]
then
	TZ='UTC'
fi

# Config system
./start_osticket-config.sh

# Automate installation
php /var/www/osticket/upload/setup/install.php
rm -r /var/www/osticket/upload/setup
chmod 644 /var/www/osticket/upload/include/ost-config.php

# Launch Apache2 as Apache user
/usr/sbin/httpd -D FOREGROUND

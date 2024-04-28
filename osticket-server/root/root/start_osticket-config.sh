#!/bin/ash

# Set the timezone of the OS
if [ -z "${TZ}" ]
then
	TZ='UTC'
fi
ln -s /usr/share/zoneinfo/$TZ /etc/localtime

# Modify default timezone for PHP
sed -i "s|;date.timezone =|date.timezone=${TZ}|" /etc/php82/php.ini

# Modify default cookie_httponly value for security purpose
sed -i "s|session.cookie_httponly =|session.cookie_httponly = 1|" /etc/php82/php.ini

# Modify maximum amount of memory a script may consume
sed -i "s|memory_limit = 128M|memory_limit = 256M|" /etc/php82/php.ini

# Modify maximum execution time of each script, in seconds
sed -i "s|max_execution_time = 30|max_execution_time = 600|" /etc/php82/php.ini

# Modify maximum upload file size
sed -i "s|upload_max_filesize = 2M|upload_max_filesize=100M|" /etc/php82/php.ini

# Modify maximum post file size
sed -i "s|post_max_size = 8M|post_max_size=100M|" /etc/php82/php.ini

# Modify mail path
sed -i "s|;sendmail_path =|sendmail_path=\"/usr/bin/msmtp -C /etc/msmtp -t\"|" /etc/php82/php.ini

# Hide the use of PHP
sed -i "s|expose_php = On|expose_php = Off|" /etc/php82/php.ini

# Enable APCU
echo "apc.enabled=1" >> /etc/php82/php.ini
echo "apc.ttl=7200" >> /etc/php82/php.ini

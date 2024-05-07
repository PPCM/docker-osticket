# syntax=docker/dockerfile:1

# Dockerfile for osTicket
# Compile : docker build --no-cache -t ppcm/osticket-server .
# Build multiplateform : docker buildx build --no-cache --push --platform linux/386,linux/amd64,linux/arm/v6,linux/arm64,linux/arm --tag ppcm/glpi-server:latest .

#ENV OSTICKET_URL : URL for osTicket instance
#ENV MYSQL_HOST : address/host name of the MySQL server
#ENV MYSQL_PORT : define port of the MySQL server
#ENV MYSQL_USER : MySQL user to use for osTicket
#ENV MYSQL_PASSWORD : Password to use for MySQL user for osTicket
#ENV MYSQL_PREFIX : table prefix to use for each table needed by osTicket
#ENV SMTP_HOST : The host name (or IP address) of the SMTP server to send all outgoing mail through. Defaults to 'localhost'.
#ENV SMTP_PORT : The TCP port to connect to on the server. Defaults to '25'. Usually one of 25, 465 or 587.
#ENV SMTP_FROM : The envelope from address to use when sending email (note that is not the same as the From: header). This must be provided for sending mail to function. However, if not specified, this will default to the value of SMTP_USER if this is provided.
#ENV SMTP_TLS : Boolean (1 or 0) value indicating if TLS should be used to create a secure connection to the server. Defaults to true.
#ENV SMTP_TLS_CERTS : If TLS is in use, indicates file containing root certificates used to verify server certificate. Defaults to system installed ca certificates list. This would normally only need changed if you are using your own certificate authority or are connecting to a server with a self signed certificate.
#ENV SMTP_USER : The user identity to use for SMTP authentication. Specifying a value here will enable SMTP authentication. This will also be used for the SMTP_FROM value if this is not explicitly specified. Defaults to no value.
#ENV SMTP_PASSWORD : The password associated with the user for SMTP authentication. Defaults to no value.
#ENV CRON_INTERVAL : Specifies how often (in minutes) that osTicket cron script should be ran to check for incoming emails. Defaults to 5 minutes. Note that this works in conjuction with the email check interval specified in the admin control panel, you need to specify both to the value you'd like!
#ENV LANG : Language to use for osTicket UI
#ENV TZ - specify a timezone to use, see this [list](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones#List)


# VOLUME config - Persistence for osTicket Config
# VOLUME data - Persistence for osTicket stored files

FROM alpine:3.19.1

ENV NODE_ENV=production

# Variables for versions
ENV OSTICKET_VERSION="v1.18.1"
ENV OSTICKET_LANG_VERSION="1.18.x"

RUN apk --no-cache upgrade \
    && apk add --no-cache \
							mariadb-client \
		          apache2 php82-apache2 php82 \
						  php82-curl php82-gd php82-mbstring php82-mysqli php82-session php82-pecl-apcu php82-imap php82-cgi \
							php82-simplexml php82-xml php82-xmlreader php82-xmlwriter php82-intl php82-dom php82-soap php82-fileinfo \
						  php82-ldap php82-phar php82-zip php82-bz2 php82-opcache php82-pear php82-iconv php82-pdo php82-sockets \
						  wget unzip inotify-tools tzdata

# Working directory to root
WORKDIR /root

# Installation osTicket
RUN --mount=type=bind,source=utils/verify-plugin.php,target=/tmp/verify-plugin.php,readonly \
  set -ex; \
	# Installation of osTicket
	wget https://github.com/osTicket/osTicket/releases/download/${OSTICKET_VERSION}/osTicket-${OSTICKET_VERSION}.zip; \
  unzip osTicket-${OSTICKET_VERSION}.zip -d /var/www/osticket; \
	rm osTicket-${OSTICKET_VERSION}.zip; \
	cd /var/www/osticket/upload; \
	# Installation of language packs
	for lang in bg bn bs ca cs da de el es_AR es_ES es_MX et eu fa fi fr gl he hi hr hu id is it \
		  				ja ka km ko lt lv mk mn ms nl no pl pt_BR pt_PT ro ru sk sl sq sr sr_CS sv_SE sw th tr uk \
							ur_IN ur_PK vi zh_CN zh_TW; \
		do \
			# This URL is the same as what is used by the official osTicket Downloads page. This URL is
			# used even for minor versions >= 14.
			wget -q -O include/i18n/${lang}.phar \
								https://s3.amazonaws.com/downloads.osticket.com/lang/${OSTICKET_LANG_VERSION}/${lang}.phar; \
			php /tmp/verify-plugin.php "/var/www/osticket/upload/include/i18n/${lang}.phar"; \
		done; \
	# Installation of plugins
	for plugin in audit auth-2fa auth-ldap auth-oauth2 auth-passthru auth-password-policy \
                storage-fs storage-s3; \
	  do \
        wget -q -O include/plugins/${plugin}.phar \
            https://s3.amazonaws.com/downloads.osticket.com/plugin/${plugin}.phar; \
    done; \
	# This checks `.phar` integrity (authenticity check is not supported - see
	# https://github.com/osTicket/osTicket/issues/6376).
	for phar in include/plugins/*.phar; \
	  do \
        # The following PHP code throws an exception and returns non-zero if .phar can't be loaded
        # (e.g. due to a checksum mismatch)
        php -r "new Phar(\"${phar}\");"; \
    done; \
	# Create a link to the config file
	ln -s /config/ost-config.php /var/www/osticket/upload/include/ost-config.php; \
	chown -R apache:apache /var/www/osticket; \
	mkdir /config; \
	chown apache:apache /config; \
	mkdir /data; \
	chown apache:apache /data

# osTicket installation script
COPY root /
COPY install.php /var/www/osticket/upload/setup/

# osTicket start scripts
RUN chmod a+x /root/start_osticket-server.sh /root/start_osticket-config.sh

# Open port for httpd access
EXPOSE 80

# Mount volumes persistence
VOLUME /config
VOLUME /data

# Run Start script
ENTRYPOINT ["/root/start_osticket-server.sh"]
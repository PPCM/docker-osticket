# syntax=docker/dockerfile:1

# Dockerfile for osTicket
# Compile : docker build --no-cache -t ppcm/osticket-server .
# Build multiplateform : docker buildx build --no-cache --push --platform linux/386,linux/amd64,linux/arm/v6,linux/arm64,linux/arm --tag ppcm/glpi-server:latest .

#ENV CRON_SCHEDULE - Schedule in CRON format - [cron.guru](https://crontab.guru/) can help you to define it - By default the value is "*/2 * * * *"
#ENV TZ - specify a timezone to use, see this [list](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones#List)


# VOLUME config - Persistence for osTicket Config
# VOLUME data - Persistence for osTicket stored files

FROM ppcm/osticket-server

ENV NODE_ENV=production

# Remove unused packages
RUN apk --no-cache upgrade \
    && apk del --no-cache \
		          apache2 php82-apache2

# osTicket installation script
COPY root /

# osTicket start scripts
RUN \
  set -ex; \
  chmod a+x /root/start_osticket-cron.sh; \
  rm start_osticket-server.sh

# Mount volumes persistence
VOLUME /config
VOLUME /data

# Run Start script
ENTRYPOINT ["/root/start_osticket-cron.sh"]
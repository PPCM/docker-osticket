# osTicket Docker Container

## Supported tags

- 1, 1.18, 1.18.1, 1.18.1-4, latest

## Quick reference

- Where to file issues: https://github.com/PPCM/docker-osticket/issues
- Supported architectures: ([more info](https://github.com/docker-library/official-images#architectures-other-than-amd64)) amd64 386 arm/v6 arm/v7 arm64
- These images have been freely inspired from the original docker-osticket image by [devinsolutions](https://github.com/devinsolutions/docker-osticket).


## What is osTicket

osTicket is a widely-used open source support ticket system. It seamlessly integrates inquiries created via email, phone and web-based forms into a simple easy-to-use multi-user web interface. Manage, organize and archive all your support requests and responses in one place while providing your customers with accountability and responsiveness they deserve.

[![osTicket Logo](https://osticket.com/wp-content/uploads/2021/03/osticket-supsys-new-1-e1616621912452.png)](https://osticket.com/)

## About Docker osTicket

This package contains with:
- OS: Debian
- Web Server: Apache2 / PHP
- osTicket application
- All available language packs (see https://osticket.com/download)
- All available plugins (see https://osticket.com/download/)

Description of each image
- `ppcm/osticket-server` : osTicket web server with the UI
- `ppcm/osticket-cron` : osTicket cron job daemon is running with scheduling managed

## How to use this images

### Start `osTicket` with docker
Starting a `osTicket` instance is simple

```console
$ docker network create some-network 
$ docker run -d --name some-mariadb -p 3306:3306 --network some-network -e MARIADB_USER=osticket-user -e MARIADB_PASSWORD=osticket-password -e MARIADB_RANDOM_ROOT_PASSWORD=1 -e MARIADB_DATABASE=osticket -v mysql-dir:/var/lib/mysql mariadb:latest
$ docker run -d --name some-osticket-server -p 8089:80 --network some-network -e MYSQL_HOST=some-mariadb -e MYSQL_PORT=3306 -e MYSQL_USER=osticket-user -e MYSQL_PASSWORD=osticket-password -e MYSQL_DATABASE=osticket -e INSTALL_LANG_ID=fr -e TZ="Europe/Paris" -v config:/config -v data:/data ppcm/osticket-server:latest
$ docker run -d --name some-osticket-cron --network some-network -e CRON_SCHEDULE="*/2 * * * *" -e TZ="Europe/Paris" -v config:/config -v data:/data ppcm/osticket-cron:latest
```
### Login to osTicket

By default, the following users are created

| function                   | login     | password |
|:---------------------------|:----------|:---------|
| Administrator              | ostadmin  | ostadmin |

You are invited to change as soon as possible password of this account or to remove them.

### Docker informations

#### Exposed ports

| Port      | mariadb | ppcm/osticket-server | Usage                         |
|:---------:|:-------:|:--------------------:|:-----------------------------:|
| 80/tcp    |         | X                    | HTTP web application          |

For SSL, there are many different possibilities to introduce encryption depending on your setup.

As most of available docker image on the internet, it is recommend using a reverse proxy in front of this image. This prevent to introduce all ssl configurations parameters and also to prevent a limitation of the available parameters.

For example, you can use the popular nginx-proxy and docker-letsencrypt-nginx-proxy-companion containers or Traefik to handle this.

#### Environments variables
For plugins variables, any content, except 0, will install, update and activate the plugin.

| Environment                       | mariadb | ppcm/osticket | ppcm/osticket-cron | Default                | Usage                                                                              |
|:----------------------------------|:-------:|:-------------:|:------------------:|:----------------------:|:-----------------------------------------------------------------------------------|
| MYSQL_HOST                        |         | X             |                    |                        | MANDATORY - MySQL or MariaDB host name                                             |
| MYSQL_PORT                        |         | X             |                    | 3306                   | MySQL or MariaDB host port                                                         |
| MYSQL_USER                        | X       | X             |                    | osticket               | MySQL or MariaDB osTicket username                                                 |
| MYSQL_PASSWORD                    | X       | X             |                    | osticket               | MySQL or MariaDB password for osTicket user                                        |
| MYSQL_DATABASE                    | X       | X             |                    | osticket               | MySQL or MariaDB database name for osTicket                                        |
| MYSQL_PREFIX                      |         | X             |                    | ost_                   | MySQL Table Prefix                                                                 |
| TZ                                |         | X             | X                  | UTC                    | Timezone for the web server and for osTicket                                       |
| INSTALL_NAME                      |         | X             |                    | My Helpdesk            | Helpdesk Name                                                                      |
| INSTALL_EMAIL                     |         | X             |                    | helpdesk@example.com   | Default Email                                                                      |
| INSTALL_URL                       |         | X             |                    | http://localhost:8080/ | Helpdesk URL                                                                       |
| INSTALL_LANG_ID                   |         | X             |                    | en_US                  | Primary Language                                                                   |
| INSTALL_SECRET                    |         | X             |                    |                        | Secret string value for osTicket installation (see below)                          |
| CRON_SCHEDULE                     |         |               | X                  | */2 * * * *            | Schedule in CRON format - [cron.guru](https://crontab.guru/) can help you          |
| SMTP_HOSTNAME                     |         | X             |                    | localhost              | The host name (or IP address) of the SMTP server to send all outgoing mail through |
| SMTP_PORT                         |         | X             |                    | 25                     | The TCP port to connect to on the server. Usually one of 25, 465 or 587.           |
| SMTP_FROM                         |         | X             |                    |                        | The envelope from address to use when sending email (note that is not the same as the From: header). This must be provided for sending mail to function. However, if not specified, this will default to the value of `SMTP_USER` if this is provided. |
| SMTP_TLS                          |         | X             |                    | 1                      | Boolean (1 or 0) value indicating if TLS should be used to create a secure connection to the server |    
| SMTP_TLS_CERTS                    |         | X             |                    | /etc/ssl/certs/ca-certificates.crt | If TLS is in use, indicates file containing root certificates used to verify server certificate |    
| SMTP_USER                         |         | X             |                    |                        | The user identity to use for SMTP authentication. Specifying a value here will enable SMTP authentication |    
| SMTP_PASSWORD                     |         | X             |                    |                        | The password associated with the user for SMTP authentication                      |

#### Exposed volumes
Volumes must be exposed for `ppcm/osticket-server` and `ppcm/osticket-cron`

| Volume  | Usage                                      |
|:--------|:-------------------------------------------|
| /config | Volume for configuration files of osTicket |
| /data   | Volume for any data of osTicket            |

#### osTicket Cronjob

osTicket require a job to be run periodically.
To respect docker convention and to prevent a clustered deploiement to run the cron on all cluster instances, the cron task was removed from osTicket main image.

As compensation a dedicated image `ppcm/osticket-cron` was made for the cron task. Only one instance of this image has to run on your cluster.

#### Mail Configuration

The image does not run a MTA. Although one could be installed quite easily, getting the setup so that external mail servers will accept mail from your host & domain is not trivial due to anti-spam measures. This is additionally difficult to do from ephemeral docker containers that run in a cloud where the host may change etc.

Hence this image supports osTicket sending of mail by sending directly to designated a SMTP server. However, you must provide the relevant SMTP settings through environmental variables before this will function.

To automatically collect email from an external IMAP or POP3 account, configure the settings for the relevant email address in your admin control panel as normal (Admin Panel -> Emails).

#### Environmental Variables

##### `INSTALL_SECRET`

Secret string value for osTicket installation. A random value is generated on start-up and persisted in `/data/secret.txt` if this is not provided.

If using in production you should specify this so that re-creating the container does not cause your installation secret to be lost!
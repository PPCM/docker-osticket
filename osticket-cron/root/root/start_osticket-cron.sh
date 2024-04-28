#!/bin/ash

# Modify schedule of CRON config file
if [ -z "${CRON_SCHEDULE}" ]
then
    CRON_SCHEDULE="*/2 * * * *"
fi
sed -i "s|%%SCHEDULE%%|${CRON_SCHEDULE}|" /etc/crontabs/root

# Config system
./start_osticket-config.sh

# Waiting for the installation to be done
echo `date` " - Waiting GLPI to be installed from ppcm/glpi-server"
secret_file='/config/secret.txt'
config_file='/config/ost-config.php'
while [ ! -f "$secret_file" -o ! -f "$config_file" ]
do
    inotifywait -qq -t 30 -e create -e moved_to "$(dirname $secret_file)" "$(dirname $config_file)"
done

## Sleep 60s before continue
sleep 60s

echo `date` " - Start CRON job"

# Run osTicket cron script
/usr/sbin/crond -f -L /dev/stdout -c /etc/crontabs

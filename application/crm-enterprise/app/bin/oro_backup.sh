#!/bin/bash
# This script does total code and database backup safely with maintenance mode #

set -e

# Includes #
source app/config/oro_env.conf;
source app/bin/oro_env.stdlib;

# Testing if we have all set #

if [ ! -f $DB_CONF ]; then
    echo "OroCRM configuration file not found! Aborting!"
    exit 1;
fi

# Main #

app/bin/oro_maintenance_on.sh;
if [ -f /tmp/oro-jobs.lock ]; then
    rm -f /tmp/oro-jobs.lock;
    exit 1;
fi
app/bin/oro_code_backup.sh;
app/bin/oro_db_backup.sh;
app/bin/oro_maintenance_off.sh;

exit 0;

# EOF #

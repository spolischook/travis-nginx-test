#!/bin/bash
# This script does total code and dataabase restore from prior backup safely with maintenance mode #

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
app/bin/oro_code_restore.sh;
cd $WEB_ROOT/$ORO_NAME;
app/bin/oro_db_restore.sh;
app/bin/oro_maintenance_off.sh;

exit 0;

# EOF #

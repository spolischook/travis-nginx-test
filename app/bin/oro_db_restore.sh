#!/bin/bash
# Oro dataabase restore from backup shell script #

# Includes #
source app/config/oro_env.conf;
source app/bin/oro_env.stdlib;

# Variables #
DUMP="$1"

# Testing if we have all set #

if [ ! -f $DB_CONF ]; then
    echo "Oro backup configuration not found, aborting!"
    exit 1;
fi

if [ ! -d "$DB_BACKUP_DIR" ]; then
    echo "Directory with backups not found, aborting!";
    exit 1;
fi

# Main #

if [ $DB_TYPE == "pdo_mysql" ]; then
    mysql_restore;
elif [ $DB_TYPE == "pdo_pgsql" ]; then
    pgsql_restore;
else
    echo "Invalid Database driver, aborting!"
    exit 1;
fi

# EOF #

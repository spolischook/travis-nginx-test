#!/bin/bash
# This Oro database backup script for periodic backups #

set -e

# Includes #
source app/config/oro_env.conf;
source app/bin/oro_env.stdlib;

# Testing if we have all set #

if [ ! -f $DB_CONF ]; then
    echo "Oro backup configuration not found, aborting!"
    exit 1;
fi

if [ ! -f $DB_CONF ]; then
    echo "OroCRM configuration file not found, aborting!"
    exit 1;
fi

if [ ! -d "$DB_BACKUP_DIR" ]; then
    mkdir -p $DB_BACKUP_DIR;
fi

# Main #

if [ $DB_TYPE == "pdo_mysql" ]; then
    db_rotate;
    mysql_backup;
elif [ $DB_TYPE == "pdo_pgsql" ]; then
    db_rotate;
    pgsql_backup;
else
    echo "Invalid Database driver, aborting!"
    exit 1;
fi

# EOF #

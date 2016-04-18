#!/bin/bash
# Oro dataabase backup shell script #

# Includes #
source app/config/oro_env.conf;
source app/bin/oro_env.stdlib;

# Testing if we have all set #

if [ ! -f $DB_CONF ]; then
    echo "Oro backup configuration not found, aborting!"
    exit 1;
fi

if [ ! -d "$DB_BACKUP_DIR" ]; then
    mkdir -p $DB_BACKUP_DIR;
fi

# Main #

if [ $DB_TYPE == "pdo_mysql" ]; then
    mysql_backup $1;
elif [ $DB_TYPE == "pdo_pgsql" ]; then
    pgsql_backup $1;
else
    echo "Invalid Database driver, aborting!"
    exit 1;
fi

# EOF #

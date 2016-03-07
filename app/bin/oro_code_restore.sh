#!/bin/bash
# OroCRM code restore from backup script #

# Includes #
source app/config/oro_env.conf;
source app/bin/oro_env.stdlib;

# Testing if we have all set #

if [ ! -f $DB_CONF ]; then
    echo "OroCRM configuration file not found! Aborting!"
    exit 1;
fi

if [ ! -d "$CODE_BACKUP_DIR" ]; then
    echo "Directory with backups not found, aborting!";
    exit 1;
fi

# Main #

code_restore;

# EOF #

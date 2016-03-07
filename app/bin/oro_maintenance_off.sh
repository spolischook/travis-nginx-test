#!/bin/bash
# OroCRM Maintenance mode disable tool #

# Includes #
source app/config/oro_env.conf;
source app/bin/oro_env.stdlib;

# Testing if we have all set #

if [ ! -f $DB_CONF ]; then
    echo "Oro backup configuration not found, aborting!"
    exit 1;
fi

# Main #

maintenance_off;

# EOF #

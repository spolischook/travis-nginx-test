#!/bin/bash
# OroCRM Maintenance mode enable tool #

set -e

# Includes #
source app/config/oro_env.conf;
source app/bin/oro_env.stdlib;

# Testing if we have all set #

if [ -f $LOCK_FILE ]; then
    echo "Maintenance is already enabled, aborting!"
    exit 1;
fi

# Main #

maintenance_on;

# EOF #

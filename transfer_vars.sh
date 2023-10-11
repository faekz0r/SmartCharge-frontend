#!/bin/bash

# export all variables

set -a
. /var/www/SmartCharge/vars.sh
set +a 

echo "start hour:" $start_hour

env

envsubst < /home/being/SmartCharge/user_vars.sh.template > /home/being/SmartCharge/user_vars.sh


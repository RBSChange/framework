#!/bin/bash

# This is the production version of dayChange.sh script
# Please verify that php location is effectively /usr/bin/php (which php)

if [ $(id -u) -eq 0 ]; then
        echo "You are root. It is not a good idea."
        exit 1
fi

echo "** Start $(date) **"

cd /home
for user in *
do
  project=/home/${user}
  if [ -f "${project}/profile" ]; then
     echo -n "$project... "
     (/usr/bin/php ${project}/webapp/bin/tasks/dayChange.php && echo "OK") || echo "KO"
  fi
done 

echo "** End $(date) **"

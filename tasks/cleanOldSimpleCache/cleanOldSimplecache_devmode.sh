#!/bin/bash

# This is the development mode (development servers use a different layout)
# version of cleanOldSimpleCache.sh script

if [ $(id -u) -eq 0 ]; then
        echo "You are root. It is not a good idea."
        exit 1
fi

echo "** Start $(date) **"

cd /home
for user in *
do
  echo $user
  for project in /home/${user}/change4/*
  do
    if [ -f "${project}/profile" ]; then
      profile=$(cat ${project}/profile)
      oldCacheDir=${project}/cache/${profile}/simplecache/old/
      [ -d "$oldCacheDir" ] && (echo "  $oldCacheDir"; rm -r $oldCacheDir)
    fi
  done
done 

echo "** End $(date) **"

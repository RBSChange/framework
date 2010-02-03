#!/bin/bash

# This is the production cleanOldSimpleCache.sh script

if [ $(id -u) -eq 0 ]; then
        echo "You are root. It is not a good idea."
        exit 1
fi

echo "** Start $(date) **"

cd /home
for user in *
do
  if [ -f "/home/${user}/profile" ]; then
    profile=$(cat /home/${user}/profile)
    oldCacheDir=/home/${user}/cache/${profile}/simplecache/old/
    [ -d "$oldCacheDir" ] && (echo "$oldCacheDir"; rm -r $oldCacheDir)
  fi
done 

echo "** End $(date) **"

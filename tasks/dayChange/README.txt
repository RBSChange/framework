In system crontab, please add (change 'www-data' to your web server's unix group if needed):
# run dayChange task every day, at 00:01
1 0 * * * su www-data '/bin/bash /root/bin/dayChange.sh > /tmp/dayChange.log.err 2>&1'

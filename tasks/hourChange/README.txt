In system crontab, please add (change 'www-data' to your web server's unix group if needed):
# run hourChange task every hour
1 * * * * su www-data '/bin/bash /root/bin/hourChange.sh > /tmp/hourChange.log.err 2>&1'

This script could be installed as following in the system (root level) crontab: 
0 * * * * su www-data '/bin/bash /root/bin/cleanOldSimplecache.sh' > /dev/null 2>&1
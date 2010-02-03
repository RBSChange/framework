1) dayChange.php

In www-data's crontab, please add:
# run dayChange task every day, at 00:01
1 0  * * * php ${WEBEDIT_HOME}/webapp/bin/tasks/dayChange.php

You must replace ${WEBEDIT_HOME} by your project path

2) hourChange.php
In www-data's crontab, please add:
# run hourChange task every hour
1 */1  * * * php ${WEBEDIT_HOME}/webapp/bin/tasks/hourChange.php
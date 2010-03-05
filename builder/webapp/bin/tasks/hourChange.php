<?php
// In apache user's crontab, please add:
// # run hourChange task every hour
// 1 */1  * * * php ${WEBEDIT_HOME}/webapp/bin/tasks/hourChange.php
require_once("BaseTask.php");

class f_tasks_HourChangeTask extends f_tasks_BaseTask
{
	function __construct()
	{
		parent::__construct("hourChange");
	}
	
	protected function execute($previousRunTime)
	{
		$this->loadFramework();
		$date = date_Calendar::now()->toString();
		if (Framework::isDebugEnabled())
		{
			Framework::debug('Hour change: '. $date);
		}

		f_event_EventManager::dispatchEvent('hourChange', null, array('date' => $date, 'previousRunTime' => $previousRunTime));
	}
}

$hourChange = new f_tasks_HourChangeTask();
$hourChange->start();
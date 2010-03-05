<?php
// In www-data's crontab, please add:
// # run dayChange task every day, at 00:01
// 1 0  * * * php ${WEBEDIT_HOME}/webapp/bin/tasks/dayChange.php
require_once("BaseTask.php");

class f_tasks_DayChangeTask extends f_tasks_BaseTask
{
	function __construct()
	{
		parent::__construct("dayChange");
	}

	protected function execute($previousRunTime)
	{
		$this->loadFramework();
		$date = date_Calendar::now()->toString();
		if (Framework::isDebugEnabled())
		{
			Framework::debug('Day change: '. $date);
		}

		f_event_EventManager::dispatchEvent('dayChange', null, array('date' => $date, 'previousRunTime' => $previousRunTime));
	}
}

$dayChange = new f_tasks_DayChangeTask();
$dayChange->start();
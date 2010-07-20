<?php
ignore_user_abort(true);
define('WEBEDIT_HOME', dirname(realpath(__FILE__)));
if (file_exists(WEBEDIT_HOME."/site_is_disabled"))
{
	exit(0);
}

require_once WEBEDIT_HOME . "/framework/Framework.php";
if (defined('DISABLE_CHANGECRON_EXECUTION') && constant('DISABLE_CHANGECRON_EXECUTION') == true)
{
	exit(0);
}
if (Framework::isInfoEnabled())
{
	Framework::info($_SERVER['REMOTE_ADDR'] ." - ". $_SERVER['REQUEST_URI']);
}

//Exectut Task
if (isset($_GET['taskId']))
{
	chdir(WEBEDIT_HOME);
	Controller::newInstance("controller_ChangeController");
	try
	{
		$runnableTask = DocumentHelper::getDocumentInstance(intval($_GET['taskId']));
		task_PlannedTaskRunner::executeSystemTask($runnableTask);
	}
	catch (Exception $e)
	{
		Framework::exception($e);
	}
	exit();	 
}


if (!isset($_GET['token'])) 
{
	//First call to script
	$token = strval(microtime(true));
	task_PlannedTaskRunner::setChangeCronToken($token);
	reloadCron($token, false);
	exit();
}

//Ping Call to script
$token = $_GET['token'];
register_shutdown_function('reloadCron', $token, true);
task_PlannedTaskRunner::main();
function reloadCron($token, $sleepOnPing)
{
	$previousToken  = task_PlannedTaskRunner::getChangeCronToken();
	if ($previousToken !== null && $token != $previousToken) 
	{
		exit();
	}
	if ($sleepOnPing) 
	{
		sleep(30);
	}
	$pingURl = task_PlannedTaskRunner::buildPingURL($token);
	task_PlannedTaskRunner::pingChangeCronURL($pingURl);
}
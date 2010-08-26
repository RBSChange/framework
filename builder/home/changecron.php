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
	Framework::info($_SERVER['SERVER_NAME'] ." ".  $_SERVER['SERVER_PORT'] . " " .$_SERVER['REQUEST_URI']);
}

if (defined('NODE_NAME') && ModuleService::getInstance()->moduleExists('clustersafe'))
{
	$node = clustersafe_WebnodeService::getInstance()->getCurrentNode();
	$baseURL = $node->getBaseUrl();
}
else
{
	$baseURL = Framework::getBaseUrl();
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
	reloadCron($token, $baseURL, false);
	exit();
}

//Ping Call to script
$token = $_GET['token'];
register_shutdown_function('reloadCron', $token, $baseURL, true);
task_PlannedTaskRunner::main($baseURL);

function reloadCron($token, $baseURL, $sleepOnPing)
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
	
	if ($token)
	{
		$pingURl  = $baseURL .'/changecron.php?token=' . urlencode($token);
	}
	else
	{
		$pingURl  = $baseURL .'/changecron.php';
	}

	task_PlannedTaskRunner::pingChangeCronURL($pingURl);
}
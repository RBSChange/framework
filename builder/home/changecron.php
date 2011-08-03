<?php
ignore_user_abort(true);
define('PROJECT_HOME', dirname(realpath(__FILE__)));
define('WEBEDIT_HOME', PROJECT_HOME);
if (file_exists(PROJECT_HOME."/site_is_disabled"))
{
	exit(0);
}

require_once PROJECT_HOME . "/framework/Framework.php";
if (defined('DISABLE_CHANGECRON_EXECUTION') && constant('DISABLE_CHANGECRON_EXECUTION') == true)
{
	exit(0);
}

Framework::info($_SERVER['SERVER_NAME'] ." ".  $_SERVER['SERVER_PORT'] . " " .$_SERVER['REQUEST_URI']);

RequestContext::getInstance()->setMode(RequestContext::BACKOFFICE_MODE);


if (defined('NODE_NAME') && ModuleService::getInstance()->moduleExists('clustersafe'))
{
	$node = clustersafe_WebnodeService::getInstance()->getCurrentNode();
	if (!$node->isPublished()) 
	{
		Framework::info('Node deactivated cron stoped.');
		exit(0);
	}
	$baseURL = $node->getBaseUrl();
}
else
{
	$baseURL = Framework::getBaseUrl();
}


//Exectut Task
if (isset($_GET['taskId']))
{
	chdir(PROJECT_HOME);
	change_Controller::newInstance('change_Controller');
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
		$pingURl  = $baseURL .'/changecron.php?token=' . urlencode($token) . '&t=' . time();
	}
	else
	{
		$pingURl  = $baseURL .'/changecron.php?t=' . time();
	}

	task_PlannedTaskRunner::pingChangeCronURL($pingURl);
}

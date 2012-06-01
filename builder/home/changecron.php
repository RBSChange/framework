<?php
ignore_user_abort(true);
if (!defined('PROJECT_HOME'))
{
	define('PROJECT_HOME', dirname(realpath(__FILE__)));
}

if (file_exists(PROJECT_HOME."/site_is_disabled"))
{
	exit(0);
}

require_once PROJECT_HOME . "/framework/Framework.php";
if (defined('CHANGECRON_EXECUTION') && constant('CHANGECRON_EXECUTION') != 'http')
{
	Framework::info(__FILE__ . ' Disabled');
	exit(0);
}

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
if (isset($_GET['taskId']) && is_numeric($_GET['taskId']))
{
	$runnableTask = DocumentHelper::getDocumentInstanceIfExists($_GET['taskId']);
	if ($runnableTask instanceof task_persistentdocument_plannedtask)
	{
		try
		{
			chdir(PROJECT_HOME);
			task_PlannedTaskRunner::executeSystemTask($runnableTask);
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		exit();
	}
}
else
{
	if (isset($_SERVER['REMOTE_ADDR']))
	{
		Framework::info($_SERVER['SERVER_NAME'] ." ".  $_SERVER['SERVER_PORT'] . " " .$_SERVER['REQUEST_URI']);
	}
	else
	{
		Framework::info("console exec ".  __FILE__);
	}
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

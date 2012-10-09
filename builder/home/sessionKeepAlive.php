<?php
define('PROJECT_HOME', dirname(realpath(__FILE__)));

// Starts the application
require_once PROJECT_HOME . '/Change/Application.php';
\Change\Application::getInstance()->start();

$controller = change_Controller::newInstance('change_Controller');
$controller->setNoCache();


RequestContext::getInstance()->setMode(RequestContext::BACKOFFICE_MODE);

$us = users_UserService::getInstance();
$userbo = $us->getCurrentBackEndUser();
if ($userbo) 
{
	$us->pingUser($userbo);
}

$userfo = $us->getCurrentFrontEndUser();
if ($userfo && $userfo !== $userbo)
{
	$us->pingUser($userfo);
}

$ka = change_Controller::getInstance()->getStorage()->read('framework_sessionKeepAlive');
if (!$ka)
{
	change_Controller::getInstance()->getStorage()->write('framework_sessionKeepAlive', $ka);
}
else
{
	change_Controller::getInstance()->getStorage()->write('framework_sessionKeepAlive', intval($ka)+1);
}


$ka = change_Controller::getInstance()->getStorage()->read('framework_sessionKeepAlive');
echo change_Controller::getInstance()->getStorage()->getChangeSessionContainer()->getManager()->getId() . ' - ' . $ka;

if (constant('CHANGECRON_EXECUTION') == 'http')
{
	if (($ka % 10) == 0)
	{
		if (defined('NODE_NAME') && ModuleService::getInstance()->moduleExists('clustersafe'))
		{
			$node = clustersafe_WebnodeService::getInstance()->getCurrentNode();
			$baseURL = $node->getBaseUrl();
		}
		else
		{
			$baseURL = Framework::getBaseUrl();
		}
		$pingURl = $baseURL .'/changecron.php';
		task_PlannedTaskRunner::pingChangeCronURL($pingURl);
	}
}

<?php
define('PROJECT_HOME', dirname(realpath(__FILE__)));
define('WEBEDIT_HOME', PROJECT_HOME);

// Starts the framework
require_once PROJECT_HOME . "/framework/Framework.php";

$controller = change_Controller::newInstance('change_Controller');
$controller->setNoCache();
RequestContext::getInstance()->setMode(RequestContext::BACKOFFICE_MODE);

users_UserService::getInstance()->pingBackEndUser();

if (!isset($_SESSION['sessionKeepAlive']))
{
	$_SESSION['sessionKeepAlive'] = 0;
}
else
{
	$_SESSION['sessionKeepAlive'] = intval($_SESSION['sessionKeepAlive']) + 1;
}



echo session_id() . ' - ' . $_SESSION['sessionKeepAlive'];

if (($_SESSION['sessionKeepAlive'] % 10) == 0)
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

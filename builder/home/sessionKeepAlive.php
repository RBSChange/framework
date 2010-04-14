<?php
define('WEBEDIT_HOME', dirname(realpath(__FILE__)));

// Starts the framework
require_once WEBEDIT_HOME . "/framework/Framework.php";

$controller = Controller::newInstance("controller_ChangeController");
$controller->setNoCache();

if (!isset($_SESSION['sessionKeepAlive']))
{
	$_SESSION['sessionKeepAlive'] = 0;
}
else
{
	$_SESSION['sessionKeepAlive'] = intval($_SESSION['sessionKeepAlive']) + 1;
}

users_UserService::getInstance()->pingBackEndUser();

echo session_id() . ' - ' . $_SESSION['sessionKeepAlive'];


if (($_SESSION['sessionKeepAlive'] % 10) == 0)
{
	$pingURl = task_PlannedTaskRunner::buildPingURL(null);
	task_PlannedTaskRunner::pingChangeCronURL($pingURl);
}

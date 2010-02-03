<?php
define('WEBEDIT_HOME', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR ));

// Starts the framework
require_once WEBEDIT_HOME . "/framework/Framework.php";

$controller = Controller::newInstance("controller_ChangeController");
$controller->setNoCache();

if (!isset($_SESSION['sessionKeepAlive']))
{
	$_SESSION['sessionKeepAlive'] = 0;
}

$_SESSION['sessionKeepAlive'] = intval($_SESSION['sessionKeepAlive']) + 1;

users_UserService::getInstance()->pingBackEndUser();

echo session_id() . ' - ' . $_SESSION['sessionKeepAlive'];

<?php
ignore_user_abort(true);
define('WEBEDIT_HOME', dirname(realpath(__FILE__)));
if (!isset($_POST['noframework']) || $_POST['noframework'] !== 'true')
{
	require_once WEBEDIT_HOME . "/framework/Framework.php";
}
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['SERVER_ADDR'] !== $_SERVER['REMOTE_ADDR'])
{
	header("HTTP/1.1 500 Internal Server Error");
	die('Unable to call "changescriptexec.php" with ' .  $_SERVER['REMOTE_ADDR'] . ' remote addres.');
}
if ($_SERVER['HTTP_USER_AGENT'] !== 'RBSChange/3.0' || $_SERVER['REQUEST_METHOD'] !== 'POST')
{
	header("HTTP/1.1 500 Internal Server Error");
	die($_SERVER['HTTP_USER_AGENT'] . ' is invalid user agent for calling "changescriptexec.php"');
}
if (isset($_POST['phpscript']) && isset($_POST['argv']) && is_array($_POST['argv']))
{
	$scriptPath = WEBEDIT_HOME .'/' . $_POST['phpscript'];	
	if (defined('FRAMEWORK_HOME'))
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info("execute $scriptPath with (" . count($_POST['argv']) . " args)");
		}
	}
	chdir(WEBEDIT_HOME);
		
	if (file_exists($scriptPath) && is_readable($scriptPath) 
		&& strrpos($scriptPath, '.php') === strlen($scriptPath) - 4)
	{
		include_once $scriptPath;
		exit();
	}
}
header("HTTP/1.1 500 Internal Server Error");
die('Unable to execute ['. $_POST['phpscript'] .'].');
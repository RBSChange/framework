<?php
ignore_user_abort(true);
define('WEBEDIT_HOME', dirname(realpath(__FILE__)));
require_once WEBEDIT_HOME . "/framework/Framework.php";
if (Framework::isInfoEnabled())
{
	Framework::info($_SERVER['REMOTE_ADDR'] ." - ". $_SERVER['REQUEST_URI']);
}
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['SERVER_ADDR'] !== $_SERVER['REMOTE_ADDR'])
{
	Framework::error('Unable to call "changescriptexec.php" with ' .  $_SERVER['REMOTE_ADDR'] . ' remote addres.');
	die();
}
if ($_SERVER['HTTP_USER_AGENT'] !== 'RBSChange/3.0')
{
	Framework::error($_SERVER['HTTP_USER_AGENT'] . ' is invalid user agent for calling "changescriptexec.php"');
	die();
}
if (isset($_POST['phpscript']) && isset($_POST['argv']) && is_array($_POST['argv']))
{
	$scriptPath = WEBEDIT_HOME .'/' . $_POST['phpscript'];	
	if (Framework::isInfoEnabled())
	{
		Framework::info("execute $scriptPath with (" . count($_POST['argv']) . " args)");
	}
	chdir(WEBEDIT_HOME);
		
	if (file_exists($scriptPath) && is_readable($scriptPath) && 
		f_util_FileUtils::getFileExtension($scriptPath) === 'php')
	{
		include_once $scriptPath;
		exit();
	}
}
Framework::error('Unable to execute ['. $_POST['phpscript'] .'].');
<?php
$argv = $_SERVER['argv'];
$scriptPath = getcwd() . DIRECTORY_SEPARATOR . $argv[1];
if (file_exists($scriptPath) && is_readable($scriptPath) && strrpos($scriptPath, '.php') === strlen($scriptPath) - 4)
{
	$useFramework = $argv[2] == '0';
	if (isset($argv[3]) && is_readable($argv[3]))
	{
		$argv = unserialize(file_get_contents($argv[3]));
	}
	else
	{
		$argv = array();
	}
	$_POST['argv'] = $argv;
	try
	{
		if ($useFramework)
		{
			define("WEBEDIT_HOME", getcwd());
			require_once WEBEDIT_HOME . "/framework/Framework.php";
			
		 	if (Framework::isInfoEnabled())
		 	{
		 		Framework::info("console execute $scriptPath with (" . (isset($_POST['argv']) ? count($_POST['argv']) : 'null') . " args)");
		 	}
		}
	 	include_once $scriptPath;
	 	exit();		
	}
	catch (Exception $e)
	{
		if ($useFramework)
		{
			Framework::exception($e);
		}
	}
}
exit(1);
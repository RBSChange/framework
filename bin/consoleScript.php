<?php
$argv = $_SERVER['argv'];
$scriptPath = getcwd() . DIRECTORY_SEPARATOR . $argv[1];
if (file_exists($scriptPath) && is_readable($scriptPath) && strrpos($scriptPath, '.php') === strlen($scriptPath) - 4)
{
	$useFramework = $argv[2] == '0';
	$arguments = array_splice($argv, 3);
	try
	{
		if ($useFramework)
		{
			define("PROJECT_HOME", getcwd());
			clearstatcache();
			require_once PROJECT_HOME . '/framework/Framework.php';
			
		 	if (Framework::isInfoEnabled())
		 	{
		 		Framework::info("console execute $scriptPath with (" . count($arguments) . " args)");
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
<?php
$argv = $_SERVER['argv'];
$scriptPath = getcwd() . DIRECTORY_SEPARATOR . $argv[1];
if (file_exists($scriptPath) && is_readable($scriptPath) && strrpos($scriptPath, '.php') === strlen($scriptPath) - 4)
{
	$useFramework = $argv[2] == '0';
	if (isset($argv[3]) && is_readable($argv[3]))
	{
		$arguments = unserialize(file_get_contents($argv[3]));
	}
	else
	{
		$arguments = array();
	}
	try
	{
		if ($useFramework)
		{
			define("PROJECT_HOME", getcwd());
			clearstatcache();
			require_once PROJECT_HOME . '/framework/Framework.php';
			Framework::initialize();
			
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
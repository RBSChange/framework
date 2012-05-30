<?php
$argv = $_SERVER['argv'];
$scriptPath = getcwd() . DIRECTORY_SEPARATOR . $argv[1];
if (file_exists($scriptPath) && is_readable($scriptPath) && strrpos($scriptPath, '.php') === strlen($scriptPath) - 4)
{
	$useFramework = $argv[2] == '0';
	$argv = array_splice($argv, 3);
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
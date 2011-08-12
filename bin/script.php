#!/usr/bin/env php
<?php
define("PROJECT_HOME", getcwd());
$profile = @file_get_contents(PROJECT_HOME . DIRECTORY_SEPARATOR . 'profile');
if ($profile === false || empty($profile))
{
	echo 'Profile not defined. Please define a profile in file ./profile.';
	exit(-1);
}
ignore_user_abort(true);
require_once PROJECT_HOME . "/framework/Framework.php";
RequestContext::getInstance()->setMode(RequestContext::BACKOFFICE_MODE);

if (count($_SERVER['argv']) >= 2)
{
	$scriptPath = PROJECT_HOME . DIRECTORY_SEPARATOR . base64_decode($_SERVER['argv'][1]);
	if (file_exists($scriptPath) && is_readable($scriptPath) && strrpos($scriptPath, '.php') === strlen($scriptPath) - 4)
	{
		$arguments = array();
		if (count($_SERVER['argv']) == 3)
		{
			$tmpFile =  base64_decode($_SERVER['argv'][2]);
			if (file_exists($tmpFile) && is_readable($tmpFile))
			{
				$arguments = unserialize(file_get_contents($tmpFile));
				@unlink($tmpFile);
			}
		}
		Framework::info('Console script:' . $scriptPath);
		include_once $scriptPath;
		exit();
	}
}
else
{
	$scriptPath = 'undefined';
}

echo 'Script not found: ' . $scriptPath;
exit(-1);
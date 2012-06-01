<?php
if (!defined("PROJECT_HOME"))
{
	define("PROJECT_HOME", getcwd());	
	$profile = @file_get_contents(PROJECT_HOME . DIRECTORY_SEPARATOR . 'profile');
	if ($profile === false || empty($profile))
	{
		header("HTTP/1.1 500 Internal Server Error");
		echo 'Profile not defined. Please define a profile in file ./profile.';
		exit(-1);
	}
}
clearstatcache();
define("HTTP_MODE", true);
ignore_user_abort(true);
set_time_limit(0);

require_once dirname(__FILE__) . '/bootstrap.php';
umask(0002);
$bootStrap = new c_ChangeBootStrap(PROJECT_HOME);
$argv = isset($_POST['argv']) ? $_POST['argv'] : array();

$clearKey = array_search('--clear', $argv);
if ($clearKey !== false)
{
	unset($argv[$clearKey]);
	$argv = array_values($argv);
	$bootStrap->cleanDependenciesCache();
}

if (count($argv) && $argv[0] === '-h')
{
	$argv = array();
}
$bootStrap->initCommands();
$bootStrap->execute($argv);
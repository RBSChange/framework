#!/usr/bin/env php
<?php
define("PROJECT_HOME", getcwd());
$profile = @file_get_contents(PROJECT_HOME . DIRECTORY_SEPARATOR . 'profile');
if ($profile === false || empty($profile))
{
	echo 'Profile not defined. Please define a profile in file ./profile.';
	exit(-1);
}
clearstatcache();
require_once dirname(__FILE__) . '/bootstrap.php';
umask(0002);
$bootStrap = new c_ChangeBootStrap(PROJECT_HOME);

$argv = array_slice($_SERVER['argv'], 1);

$clearKey = array_search('--clear', $argv);
if ($clearKey !== false)
{
	unset($argv[$clearKey]);
	$argv = array_values($argv);
	$bootStrap->cleanDependenciesCache();
}

$bootStrap->initCommands();
$bootStrap->execute($argv);
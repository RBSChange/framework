#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . "/bootstrap.php");

umask(0002);
define("WEBEDIT_HOME", getcwd());
$profile = file_get_contents(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'profile');
define('PROFILE', trim($profile) );
define('FRAMEWORK_HOME', WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'framework');
define('AG_CACHE_DIR', WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . PROFILE);

$boot = new c_ChangeBootStrap(WEBEDIT_HOME);
if (array_search('--clear', $_SERVER["argv"]))
{
	unset($_SERVER["argv"]['--clear']);
	$boot->cleanDependenciesCache();
}

$boot->dispatch("dep:framework:bin/change.php");
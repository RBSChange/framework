<?php
if (!defined("PROFILE") || !defined("WEBEDIT_HOME") || !isset($bootStrap) || !isset($script))
{
	exit(-1);
}
if (!isset($argv)) {$argv = array();}

$clearKey = array_search('--clear', $argv);
if ($clearKey !== false)
{
	unset($argv[$clearKey]);
	$argv = array_values($argv);
	$bootStrap->cleanDependenciesCache();
}

clearstatcache();

$script->setBootStrap($bootStrap);
$script->setEnvVar('computedDeps', $bootStrap->getComputedDependencies());

$addDevCmds = $bootStrap->getProperties()->getProperty('DEVELOPMENT_MODE') == true;
if ($addDevCmds && !defined('CHANGE_DEV_MODE')) {define('CHANGE_DEV_MODE', true);}

$script->loadCommands(true);
$script->execute($argv);
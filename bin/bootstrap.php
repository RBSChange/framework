<?php

function c_error($msg, $exitCode = 1)
{
	echo "[ERROR] $msg\n";
	if ($exitCode !== null)
	{
		exit($exitCode);
	}
}

if (version_compare(PHP_VERSION, '5.3.0' , '<'))
{
	c_error("PHP version (".PHP_VERSION.") < 5.3.0", true);
}

$binpath = dirname(__FILE__);
require_once $binpath .'/includes/ChangePackage.php';
require_once $binpath .'/includes/ChangeBootStrap.php';
require_once $binpath .'/includes/Configuration.php';
require_once $binpath .'/includes/ChangescriptCommand.php';

$frameworkPath = dirname($binpath);
require_once($frameworkPath . '/util/FileUtils.class.php');
require_once $frameworkPath . '/util/System.php';
require_once $frameworkPath . '/util/StringUtils.class.php';
require_once $frameworkPath . '/util/ArrayUtils.class.php';
require_once $frameworkPath . '/util/DomUtils.php';
require_once($frameworkPath . '/loader/AutoloadBuilder.class.php');
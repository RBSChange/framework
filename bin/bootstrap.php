<?php

if (!defined("C_DEBUG"))
{
	define("C_DEBUG", getenv("C_DEBUG") == "true");
}

function c_debug($msg)
{
	if (C_DEBUG)
	{
		echo "[DEBUG] $msg\n";
	}
}


function c_error($msg, $exitCode = 1)
{
	echo "[ERROR] $msg\n";
	if ($exitCode !== null)
	{
		exit($exitCode);
	}
}

function c_warning($msg)
{
	echo "[WARN] $msg\n";
}



function c_message($msg)
{
	echo $msg."\n";
}

//
function c_assert_php_version($version)
{
	if (version_compare(PHP_VERSION, $version, '>='))
	{
		c_debug("PHP Version >= $version");
	}
	else
	{
		c_error("PHP version (".PHP_VERSION.") < $version", true);
	}
}

// First thing we do is check PHP version, outside of any class (maybe running and old PHP4 version ?)
c_assert_php_version("5.1.6");


$binpath = dirname(__FILE__);
require_once $binpath .'/includes/ChangeBootStrap.php';
require_once $binpath .'/includes/ClassDirAnalyzer.php';
require_once $binpath .'/includes/Configuration.php';
require_once $binpath .'/includes/Zip.php';
require_once $binpath .'/includes/Changescript.php';
require_once $binpath .'/includes/Changescripthttp.php';
require_once $binpath .'/includes/ChangescriptCommand.php';

$frameworkPath = dirname($binpath);
require_once $frameworkPath .'/util/System.php';
require_once $frameworkPath .'/util/StringUtils.class.php';
require_once $frameworkPath .'/util/ArrayUtils.class.php';
require_once $frameworkPath .'/util/DomUtils.php';
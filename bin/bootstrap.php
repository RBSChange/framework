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
c_assert_php_version("5.3.0");

require_once FRAMEWORK_HOME .'/bin/includes/ChangeBootStrap.php';
require_once FRAMEWORK_HOME .'/bin/includes/ClassDirAnalyzer.php';
require_once FRAMEWORK_HOME .'/bin/includes/Configuration.php';
require_once FRAMEWORK_HOME .'/bin/includes/Changescript.php';
require_once FRAMEWORK_HOME .'/bin/includes/Changescripthttp.php';
require_once FRAMEWORK_HOME .'/bin/includes/ChangescriptCommand.php';

require_once FRAMEWORK_HOME .'/util/System.php';
require_once FRAMEWORK_HOME .'/util/StringUtils.class.php';
require_once FRAMEWORK_HOME .'/util/ArrayUtils.class.php';
require_once FRAMEWORK_HOME .'/util/DomUtils.php';
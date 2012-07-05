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

require_once PROJECT_HOME . '/framework/bin/includes/ChangePackage.php';
require_once PROJECT_HOME . '/framework/bin/includes/ChangeBootStrap.php';
require_once PROJECT_HOME . '/framework/bin/includes/Configuration.php';
require_once PROJECT_HOME . '/framework/bin/includes/ChangescriptCommand.php';

require_once PROJECT_HOME . '/framework/util/FileUtils.class.php';
require_once PROJECT_HOME . '/framework/util/System.php';
require_once PROJECT_HOME . '/framework/util/ArrayUtils.class.php';
require_once PROJECT_HOME . '/framework/util/DomUtils.php';
require_once PROJECT_HOME . '/framework/object/AutoloadBuilder.class.php';
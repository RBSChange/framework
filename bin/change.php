#!/usr/bin/env php
<?php
define("PROJECT_HOME", getcwd());
define("WEBEDIT_HOME", PROJECT_HOME);

$profile = @file_get_contents(PROJECT_HOME . DIRECTORY_SEPARATOR . 'profile');
if ($profile === false || empty($profile))
{
	echo 'Profile not defined. Please define a profile in file ./profile.';
	exit(-1);
}

define('PROFILE', trim($profile));

require_once dirname(__FILE__) . '/bootstrap.php';
umask(0002);
$bootStrap = new c_ChangeBootStrap(PROJECT_HOME);
$bootStrap->setAutoloadPath(PROJECT_HOME."/cache/autoload");

$argv = array_slice($_SERVER['argv'], 1);
$script = new c_Changescript(__FILE__, PROJECT_HOME . DIRECTORY_SEPARATOR . 'framework', 'change');

require("change_script.inc");
<?php
define('WEBEDIT_HOME', dirname(realpath(__FILE__)));

function KO_error_handler($errno, $errstr, $errfile, $errline)
{
	switch ($errno)
	{
		case E_USER_ERROR:
			$msg = "ERROR : [$errno] $errstr\n" .
			"  Fatal error on line $errline in file $errfile" .
			", PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
			break;

		case E_USER_WARNING:
			$msg = "WARNING : [$errno] $errstr\n";
			break;

		case E_USER_NOTICE:
			$msg = "NOTICE : [$errno] $errstr\n";
			break;

		default:
			$msg = "Unknown error type: [$errno] $errstr\n";
			break;
	}
	$GLOBALS['ERROR_MSG'] = $msg;
	KO_report();

	/* Don't execute PHP internal error handler */
	return true;
}

/**
 * @param Exception $exception
 */
function KO_exception_handler($exception)
{
	$GLOBALS['ERROR_MSG'] = "EXCEPTION : ".$exception->getMessage()."\n".$exception->getTraceAsString();
	KO_report();
}

/**
 * @param String $file
 * @param Integer $line
 * @param String $code
 */
function KO_assert_handler($file, $line, $code)
{
	$GLOBALS['ERROR_MSG'] = "Assertion failed : File '$file'\nLine '$line'\nCode '$code'\n";
	KO_report();
}

function KO_report()
{
	header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error');
	echo "KO\n";
	echo $GLOBALS['ERROR_MSG'];
	die();
}

// Settings

$ERROR_MSG = '';
error_reporting(E_ALL ^ E_NOTICE);
set_error_handler('KO_error_handler');
set_exception_handler('KO_exception_handler');
assert_options(ASSERT_CALLBACK, 'KO_assert_handler');

header('Content-Type: text/plain');

if (file_exists(WEBEDIT_HOME . "/site_is_disabled"))
{
	// Do not try anything if site is disabled
	die("OK");
}

// Tests
require_once WEBEDIT_HOME."/framework/Framework.php";
if (Framework::isDebugEnabled())
{
	Framework::debug('test_availability launched');
}

$provider = f_persistentdocument_PersistentProvider::getInstance();
assert(is_object($provider));
$tm = f_persistentdocument_TransactionManager::getInstance();
assert(is_object($tm));

try
{
	// if read/write DB separation is used, first check the read DB in frontoffice mode
	if (f_util_ClassUtils::methodExists("f_persistentdocument_PersistentProvider", "useSeparateReadWrite") && f_persistentdocument_PersistentProvider::useSeparateReadWrite())
	{
		RequestContext::getInstance()->setMode(RequestContext::FRONTOFFICE_MODE);
		$docs = $provider->createQuery('modules_users/user')->setMaxResults(1)->find();
		assert(!empty($docs));
	}

	$tm->beginTransaction();
	$docs = $provider->createQuery('modules_users/user')->setMaxResults(1)->find();
	assert(!empty($docs));
	$tm->commit();
}
catch (Exception $e)
{
	$tm->rollBack($e);
	KO_exception_handler($e);
}

Framework::debug('test_availability ended normally');
header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
die("OK");

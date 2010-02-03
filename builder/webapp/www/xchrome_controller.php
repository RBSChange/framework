<?php
$start = microtime(true);

// Set exception handler, called when an exception is not caught.
function KO_exception_handler($exception)
{
    if (strncasecmp(PHP_SAPI, 'cgi', 3))
	{
        header('HTTP/' . substr($_SERVER['SERVER_PROTOCOL'], - 3) . ' 500 Internal Server Error');
    } else
{
        header('Status: 500 Internal Server Error');
}

    Framework::exception($exception);
    $renderer = new exception_HtmlRenderer();
    $renderer->printStackTrace($exception);
}

set_exception_handler('KO_exception_handler');

define('WEBEDIT_HOME', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR));

// Starts the framework
require_once WEBEDIT_HOME . "/framework/Framework.php";


// Log request time and remote_addr.
if (Framework::isDebugEnabled())
{
    $requestId = $_SERVER['REMOTE_ADDR'] . " - " . $_SERVER['REQUEST_URI'];
    Framework::debug('|BENCH|0.0|=== START XCHROME request |'.$requestId);
}


// Instantiate controller_XulController and dispatch the request
$controller = Controller::newInstance("controller_XulController");
$controller->dispatch();

if (Framework::isDebugEnabled())
{
    $end = microtime(true);
	Framework::debug('|BENCH|'.($end-$start).'|=== END XCHROME request |'.$requestId);
	Framework::debug('|BENCH|'.(MysqlStatment::$time['exec'] + MysqlStatment::$time['read']).'|=== SQL Time |'. str_replace("\n", '', var_export(MysqlStatment::$time, true)));  
}
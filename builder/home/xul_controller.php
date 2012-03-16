<?php

define('WEBEDIT_HOME', dirname(realpath(__FILE__)));

// Set exception handler, called when an exception is not caught.
function KO_exception_handler($exception)
{
    if (strncasecmp(PHP_SAPI, 'cgi', 3))
	{
        header('HTTP/' . substr($_SERVER['SERVER_PROTOCOL'], - 3) . ' 500 Internal Server Error');
    } 
    else
	{
	    header('Status: 500 Internal Server Error');
	}

    Framework::exception($exception);
    $renderer = new exception_HtmlRenderer();
    $renderer->printStackTrace($exception);
}

if (get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}

set_exception_handler('KO_exception_handler');

// Starts the framework
require_once WEBEDIT_HOME . "/framework/Framework.php";


// Instantiate controller_XulController and dispatch the request
$controller = Controller::newInstance("controller_XulController");
$controller->dispatch();

f_persistentdocument_PersistentProvider::getInstance()->closeConnection();
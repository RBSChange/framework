<?php
define('WEBEDIT_HOME', dirname(realpath(__FILE__)));

// Set exception handler, called when an exception is not caught.
function KO_exception_handler($exception)
{
	f_persistentdocument_PersistentProvider::getInstance()->closeConnection();
	if (strncasecmp(PHP_SAPI, 'cgi', 3))
	{
		header('HTTP/'.substr($_SERVER['SERVER_PROTOCOL'], -3).' 500 Internal Server Error');
    } 
    else
	{
		header('Status: 500 Internal Server Error');
	}
	
	Framework::exception($exception);
	$renderer = new exception_HtmlRenderer();
	$renderer->printStackTrace($exception);
}
set_exception_handler('KO_exception_handler');

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
// Starts the framework
require_once WEBEDIT_HOME . "/framework/Framework.php";

// Instantiate HttpController and dispatch the request
$controller = Controller::newInstance("controller_ChangeController");
$controller->dispatch();

f_persistentdocument_PersistentProvider::getInstance()->closeConnection();

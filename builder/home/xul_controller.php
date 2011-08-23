<?php
$start = microtime(true);
define('PROJECT_HOME', dirname(realpath(__FILE__)));
define('WEBEDIT_HOME', PROJECT_HOME);

// Starts the framework
require_once PROJECT_HOME . "/framework/Framework.php";

if (Framework::isBenchEnabled())
{
	Framework::startBench($start);
	$requestId = $_SERVER['REMOTE_ADDR'] ." - ". $_SERVER['REQUEST_URI'];
	Framework::bench("START ADMIN request: ".$requestId);
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

// Instantiate controller_XulController and dispatch the request
$controller = change_Controller::newInstance('change_XulController');
$controller->dispatch();

if (Framework::isBenchEnabled())
{
	Framework::endBench("END ADMIN request: ".$requestId);
}

f_persistentdocument_PersistentProvider::getInstance()->closeConnection();
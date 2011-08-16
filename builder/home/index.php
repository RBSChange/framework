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
	Framework::bench("START CLIENT request: ".$requestId);
}

// Instantiate HttpController and dispatch the request
$controller = change_Controller::newInstance('change_Controller');
$controller->dispatch();

if (Framework::isBenchEnabled())
{
	Framework::endBench("END CLIENT request: ".$requestId);
}

f_persistentdocument_PersistentProvider::getInstance()->closeConnection();
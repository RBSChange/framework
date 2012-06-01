<?php
define('PROJECT_HOME', dirname(realpath(__FILE__)));

// Starts the framework
require_once PROJECT_HOME . "/framework/Framework.php";

// Instantiate HttpController and dispatch the request
$controller = change_Controller::newInstance('change_Controller');
$controller->dispatch();
f_persistentdocument_PersistentProvider::getInstance()->closeConnection();
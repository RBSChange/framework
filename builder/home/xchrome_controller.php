<?php
define('PROJECT_HOME', dirname(realpath(__FILE__)));

// Starts the application
require_once PROJECT_HOME . '/Change/Application.php';
\Change\Application::getInstance()->start();

// Instantiate controller_XulController and dispatch the request
$controller = change_Controller::newInstance('change_XulController');
$controller->dispatch();
f_persistentdocument_PersistentProvider::getInstance()->closeConnection();
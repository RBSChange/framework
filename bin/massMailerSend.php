<?php
// This task is executed by emailing module for mass mail sending and has not to be cronned
$controller = Controller::newInstance("controller_ChangeController");
MassMailer::getInstance()->sendMessagePaths($_POST['argv']);
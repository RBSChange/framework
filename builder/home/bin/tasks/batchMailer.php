<?php
// This task is executed by emailing module for mass mail sending and has not to be cronned
define('WEBEDIT_HOME', dirname(dirname(dirname(realpath(__FILE__)))));
chdir(WEBEDIT_HOME);
if (!file_exists(WEBEDIT_HOME . '/site_is_disabled'))
{
	require_once WEBEDIT_HOME . "/framework/Framework.php";
	RequestContext::getInstance()->setLang(RequestContext::getInstance()->getDefaultLang());	
	MassMailer::getInstance()->batchSend(array_slice($_SERVER['argv'], 1), __FILE__);
}
else 
{
	echo('WARNING: Batch mailer skipped: '.time()." (site disabled)\n"); 
}
<?php
// This task is executed by emailing module for mass mail sending and has not to be cronned
/**
 * @package framework.builder.webapp.bin.tasks
 */
define('WEBEDIT_HOME', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR  . '..' . DIRECTORY_SEPARATOR));
chdir(WEBEDIT_HOME);
if (!file_exists(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'webapp' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'site_is_disabled'))
{
	require_once WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'Framework.php';
	
	RequestContext::getInstance()->setLang(RequestContext::getInstance()->getDefaultLang());	
	MassMailer::getInstance()->batchSend(array_slice($_SERVER['argv'], 1), __FILE__);
}
else 
{
	echo('WARNING: Batch mailer skipped: '.time()." (site disabled)\n"); 
}
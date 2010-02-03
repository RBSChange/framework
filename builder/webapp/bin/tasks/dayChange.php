<?php
/**
 * @package framework.builder.webapp.bin.tasks
 */
define('WEBEDIT_HOME', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR  . '..' . DIRECTORY_SEPARATOR));
chdir(WEBEDIT_HOME);
if (!file_exists(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'webapp' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'site_is_disabled'))
{
	require_once WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'Framework.php';
	
	RequestContext::getInstance()->setLang(RequestContext::getInstance()->getDefaultLang());
	$date = date_Calendar::now()->toString();
	if (Framework::isDebugEnabled())
	{
	   Framework::debug('Day change: '. $date); 
	}
	
	f_event_EventManager::dispatchEvent('dayChange', null, array('date' => $date));
}
else 
{
	echo('WARNING: Day change skipped: '.time()." (site disabled)\n"); 
}
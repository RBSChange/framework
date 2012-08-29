<?php
class commands_ClearTemplateCache extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "";
	}
	
	function getAlias()
	{
		return "ctc";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "clear template cache directory";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Clear template cache ==");
		
		$this->loadFramework();
		CacheService::getInstance()->clearTemplateCache();
		
		$this->quitOk("Template cache directory cleared");
	}
}
<?php
class commands_ClearWebappCache extends c_ChangescriptCommand
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
		return "cwc";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "clear webapp cache directory";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Clear webapp cache ==");
		
		$this->loadFramework();
		CacheService::getInstance()->clearAllWebappCache();
		
		$this->quitOk("Webapp cache directory cleared");
	}
}
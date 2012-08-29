<?php
class commands_ClearDatacache extends c_ChangescriptCommand
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
		return "cdc";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "clear data cache";
	}
	
	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Clear data cache ==");
		$this->loadFramework();
		f_DataCacheService::getInstance()->clearCommand();
		$this->quitOk("Data cache cleared");
	}
}
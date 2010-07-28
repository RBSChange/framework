<?php
class commands_ClearSimplecache extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}
	
	function getAlias()
	{
		return "csc";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "clear simple cache directory";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Clear simple cache ==");
		
		$this->loadFramework();
		//f_util_FileUtils::cleanDir(f_util_FileUtils::buildCachePath("simplecache"));
		f_DataCacheService::getInstance()->clearCommand();
		
		$this->quitOk("Simple cache directory cleared");
	}
}
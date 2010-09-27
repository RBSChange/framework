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
		return "Deprecated use clear-datacache instead";
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
		$simpleCachePath = f_util_FileUtils::buildCachePath("simplecache");
		if (is_dir($simpleCachePath) && !(f_DataCacheService::getInstance() instanceof f_DataCacheFileService))
		{
			f_util_FileUtils::cleanDir($simpleCachePath);
		}
		$parent = $this->getParent();
		$parent->executeCommand("clearDatacache");
		
		$this->quitOk("Simple cache directory cleared");
	}
}
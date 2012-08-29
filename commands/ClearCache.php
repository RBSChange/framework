<?php
class commands_ClearCache extends c_ChangescriptCommand
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
		return "cc";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "clear cache directory";
	}

	function isHidden()
	{
		return true;
	}
	
	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Clear cache ==");
		
		$this->getBootStrap()->cleanDependenciesCache();
		
		$cacheProfileDirectory = PROJECT_HOME . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'project';
		$wwwCache = PROJECT_HOME . DIRECTORY_SEPARATOR .  'cache' . DIRECTORY_SEPARATOR . 'www';
		
		// Test if the cache directory of selected profile exist
		if (is_dir($cacheProfileDirectory))
		{
			f_util_FileUtils::rmdir($cacheProfileDirectory, true);
			if (is_dir($wwwCache))
			{
				f_util_FileUtils::rmdir($wwwCache, true);
			}
			return $this->quitOk("Cache cleared");
		}
		
		$this->executeCommand("clear-datacache");
		return $this->quitOk("Cache cleared");
	}
}
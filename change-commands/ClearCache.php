<?php
class commands_ClearCache extends commands_AbstractChangeCommand
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
		return "cc";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "clear cache directory";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Clear cache ==");

		$profile = $this->getProfile();
		if (!defined('PROJECT_HOME')) {define('PROJECT_HOME', realpath('.'));}

		$cacheProfileDirectory = PROJECT_HOME . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . $profile;
		$wwwCache = PROJECT_HOME . DIRECTORY_SEPARATOR .  'cache' . DIRECTORY_SEPARATOR . 'www';
		// Test if the cache directory of selected profile exist
		if ( is_dir( $cacheProfileDirectory ) )
		{
			f_util_FileUtils::rmdir($cacheProfileDirectory, true);
			if (is_dir($wwwCache))
			{
				f_util_FileUtils::rmdir($wwwCache, true);
			}
			return $this->quitOk("Cache cleared");
		}
		
		$parent = $this->getParent();
		$parent->executeCommand("clearDatacache");
		return $this->quitError("The profile $profile does not exists");
	}
}
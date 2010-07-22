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

		// TODO: recode... to old fashionned

		$profile = $this->getProfile();

		if ( ! defined('WEBEDIT_HOME') )
		{
			define('WEBEDIT_HOME', realpath('.'));
		}

		$cacheProfileDirectory = WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . $profile;
		$autoloadCache = $cacheProfileDirectory.DIRECTORY_SEPARATOR."autoload";
		$wwwCache = WEBEDIT_HOME . DIRECTORY_SEPARATOR .  'cache' . DIRECTORY_SEPARATOR . 'www';

		// Test if the cache directory of selected profile exist
		if ( is_dir( $cacheProfileDirectory ) )
		{
			if (is_dir($autoloadCache))
			{
				$this->deleteRecursively($autoloadCache, true);
			}
			$this->deleteRecursively($cacheProfileDirectory);
			if (is_dir($wwwCache))
			{
				$this->deleteRecursively($wwwCache);
			}

			return $this->quitOk("Cache cleared");
		}
		return $this->quitError("The profile $profile does not exists");
	}

	private function deleteRecursively( $directory, $includeDir = false)
	{
		$listOfFiles = scandir( $directory );
		foreach ($listOfFiles as $file)
		{
			$absFile = $directory . DIRECTORY_SEPARATOR . $file;
			if ( is_dir($absFile) )
			{
				if ( $file !== '.' && $file !== '..')
				{
					$this->deleteRecursively($absFile, true);
				}
			}
			else
			{
				unlink( $absFile );
			}
		}
		if ($includeDir)
		{
			rmdir($directory);
		}
	}
}
<?php
class commands_ResetDatabase extends c_ChangescriptCommand
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
		return "rdb";
	}
	
	/**
	 * @return string[]
	 */
	function getOptions()
	{
		return array('--force');
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "reset database";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Reset database ==");
		
		$this->loadFramework();
		
		if (!Framework::inDevelopmentMode())
		{
			return $this->quitError("This operation is only available in development mode.");
		}
		
		if (!isset($options['force']))
		{
			$dbInfos = f_persistentdocument_PersistentProvider::getInstance()->getConnectionInfos();
			if (!$this->yesNo("*All* tables contained ".$dbInfos["database"]."@".$dbInfos["host"]." in will be deleted. Are you sure you want to reset the database ?"))
			{
				 return $this->quitWarn("Task cancelled. No changes were performed in database.");
			}
		}
		
		//disable site bo and cron
		$this->executeCommand("disable-site");
		
		//If document cache not stored in f_cache
		$this->executeCommand("clear-documentscache");

		$this->log("Delete media...");
		
		$path = f_util_FileUtils::buildProjectPath('media','formatted');
		f_util_FileUtils::rmdir($path, true);
		
		$path = f_util_FileUtils::buildProjectPath('media','original');
		f_util_FileUtils::rmdir($path, true);
		
		$path = f_util_FileUtils::buildProjectPath('securemedia','original');
		f_util_FileUtils::rmdir($path, true);
		
		$this->executeCommand("drop-database" , array('--force'));
		$this->executeCommand("compile-documents");
		$this->executeCommand("generate-database");
		$this->executeCommand("clear-datacache");
		$this->executeCommand("compile-all");
		$this->executeCommand("indexer", array('clear'));
		$this->executeCommand("import-init-data");
		
		$this->executeCommand("theme.install");
		
		$this->executeCommand("init-patch-db");
		
		//enable bo and cron
		$this->executeCommand("enable-site");
		
		return $this->quitOk("You now need to disconnect from the backoffice and reconnect.");
	}
}
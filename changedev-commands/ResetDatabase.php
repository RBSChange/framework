<?php
class commands_ResetDatabase extends commands_AbstractChangeCommand
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
		return "rdb";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "reset database";
	}

	/**
	 * @param String[] $params
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
		
		$dbInfos = f_persistentdocument_PersistentProvider::getInstance()->getConnectionInfos();
		if (!$this->yesNo("*All* tables contained ".$dbInfos["database"]."@".$dbInfos["host"]." in will be deleted. Are you sure you want to reset the database ?"))
		{
			return $this->quitOk("Task cancelled. No changes were performed in database.");
		}
		
		$parent = $this->getParent();
		
		//disable site bo and cron
		$parent->executeCommand("disableSite");
		
		//If document cache not stored in f_cache
		$parent->executeCommand("clearDocumentscache");
		
		$parent->executeCommand("dropDatabase");
		$parent->executeCommand("compileDocuments");
		$parent->executeCommand("generateDatabase");
		$parent->executeCommand("clearDatacache");
		$parent->executeCommand("compileAll");
		$parent->executeCommand("importInitData");
		$parent->executeCommand("initPatchDb");
		
		//enable bo and cron
		$parent->executeCommand("enableSite");
		return $this->quitOk("You now need to disconnect from the backoffice and reconnect.");
	}
}
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
		$parent->executeCommand("dropDatabase");
		$parent->executeCommand("compileDocuments");
		$parent->executeCommand("generateDatabase");
		$parent->executeCommand("compileAll");
		$parent->executeCommand("importInitData");
		$parent->executeCommand("initPatchDb");
		$parent->executeCommand("clearSimplecache");
		
		$this->quitOk("You now need to disconnect from the backoffice and reconnect.");
	}
}
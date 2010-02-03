<?php
class commands_DropDatabase extends commands_AbstractChangeCommand
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
		return "ddb";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "drop database";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Drop database ==");
		
		$this->loadFramework();
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$dbInfos = $pp->getConnectionInfos();
		if (!$this->isEmbeded())
		{
			if (!Framework::inDevelopmentMode())
			{
				$this->errorMessage("This operation is only available in development mode.");
				return false;
			}

			if (!$this->yesNo("*All* tables contained ".$dbInfos["database"]."@".$dbInfos["host"]." in will be deleted. Are you sure you want to drop the database ?"))
			{
				return $this->quit("Task cancelled. No changes were performed in database.");
			}
		}
		$pp->clearDB();
		
		$this->quitOk("Database cleared successFully");
	}
}
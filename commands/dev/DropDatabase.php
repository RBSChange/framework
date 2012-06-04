<?php
class commands_DropDatabase extends c_ChangescriptCommand
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
	 * @return String[]
	 */
	function getOptions()
	{
		return array('--force');
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
		if (!Framework::inDevelopmentMode())
		{
			return $this->quitError("This operation is only available in development mode.");
		}

		$pp = f_persistentdocument_PersistentProvider::getInstance();
		if (!isset($options['force']))
		{
			$dbInfos = $pp->getConnectionInfos();
			if (!$this->isEmbeded())
			{
				if (!$this->yesNo("*All* tables contained ".$dbInfos["database"]."@".$dbInfos["host"]." in will be deleted. Are you sure you want to drop the database ?"))
				{
					return $this->quitOk("Task cancelled. No changes were performed in database.");
				}
			}
		}
		
		$pp->getSchemaManager()->clearDB();	
		return $this->quitOk("Database cleared successFully");
	}
}
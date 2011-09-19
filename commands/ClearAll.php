<?php
class commands_ClearAll extends c_ChangescriptCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}
	
	/**
	 * @return String
	 */
	function getDescription()
	{
		return "clear all";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Clear all ==");	
		$this->executeCommand("update-autoload");
		
		$this->loadFramework();
		$this->executeCommand("clear-log");
		$this->executeCommand("clear-cache");
		$this->executeCommand("clear-webapp-cache");
		$this->executeCommand("clear-datacache");
		$this->executeCommand("clear-template-cache");
		$this->executeCommand("clear-documentscache");
		$this->quitOk("All was cleared");
	}
}
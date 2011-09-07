<?php
class commands_ClearAll extends commands_AbstractChangeCommand
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
		$this->executeCommand("updateAutoload");
		
		$this->loadFramework();
		$this->executeCommand("clearLog");
		$this->executeCommand("clearCache");
		$this->executeCommand("clearWebappCache");
		$this->executeCommand("clearDatacache");
		$this->executeCommand("clearDocumentscache");
		$this->quitOk("All was cleared");
	}
}
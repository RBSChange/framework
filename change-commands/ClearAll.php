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
		
		$parent = $this->getParent();
		$parent->executeCommand("clearLog");
		$parent->executeCommand("clearCache");
		$parent->executeCommand("clearWebappCache");
		$parent->executeCommand("clearSimplecache");
		$parent->executeCommand("clearDatacache");
		$parent->executeCommand("clearDocumentscache");
		$this->quitOk("All was cleared");
	}
}
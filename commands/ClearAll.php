<?php
class commands_ClearAll extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "";
	}
	
	/**
	 * @return string
	 */
	function getDescription()
	{
		return "clear all";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Clear all ==");			
		$this->loadFramework();
		$this->executeCommand("clear-cache");
		$this->executeCommand("clear-webapp-cache");
		$this->executeCommand("clear-datacache");
		$this->executeCommand("clear-template-cache");
		$this->quitOk("All was cleared");
	}
}
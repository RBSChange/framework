<?php
class commands_Init extends commands_AbstractChangeCommand
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
		return "init";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Init ==");
		$parent = $this->getParent();
		$parent->executeCommand("init-project");
		$parent->executeCommand("init-webapp");
		$parent->executeCommand("compile-documents");
		$this->quitOk("Init OK");
	}
}
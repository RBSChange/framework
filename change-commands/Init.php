<?php
class commands_Init extends c_ChangescriptCommand
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
		$this->executeCommand("init-project");
		
		$this->loadFramework();
		$this->executeCommand("init-webapp");
		$this->executeCommand("compile-documents");
		$this->quitOk("Init OK");
	}
}
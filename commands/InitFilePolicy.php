<?php
class commands_InitFilePolicy extends c_ChangescriptCommand
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
		return "ifp";
	}
	

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Apply policy: ownership & permissions";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Init file policy ==");
		
		$this->executeCommand("apply-project-policy");
		$this->executeCommand("apply-webapp-policy");
		
		$this->quitOk("File policy initiated");
	}
}
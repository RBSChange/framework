<?php
class commands_CreateProjectModule extends commands_AbstractChangedevCommand
{
	/**
	 * @return String
	 */
	public function getUsage()
	{
		return '';
	}

	
	/**
	 * @return String
	 */
	public function getDescription()
	{
		return "create developement project module.";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Create Specific Project Module ==");
		$this->loadFramework();
		if (ModuleService::getInstance()->moduleExists('specific'))
		{
			return $this->quitError('Module specific already exist');
		}
		$this->executeCommand("add-module" , array('specific', '--hidden'));		 
	}
}
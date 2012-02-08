<?php
class commands_InitGenericModules extends commands_AbstractChangeCommand
{
	/**
	 * @return boolean
	 */
	public function isHidden()
	{
		return true;
	}
	
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
		return "init generic modules";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Deprecated Init generic modules ==");
		$this->quitOk("Please use update-dependencies");
	}
}
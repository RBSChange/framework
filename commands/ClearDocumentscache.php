<?php
class commands_ClearDocumentscache extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "";
	}
	
	function getAlias()
	{
		return "cdocc";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "clear documents cache";
	}
	
	/**
	 * @return boolean
	 */
	function isHidden()
	{
		return true;
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->quitOk("== Documents cache deactivated ==");
	}
}
<?php
class commands_CompilePhptal extends commands_AbstractChangeCommand
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
		return "ctal";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "compile PHPTal extensions";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile PHPTal extensions ==");
		
		$this->loadFramework();
		PHPTALService::getInstance()->compileAttributes();
		
		$this->executeCommand("clearWebappCache");

		$this->quitOk("PHPTal extensions compiled successfully");
	}
}
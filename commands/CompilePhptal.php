<?php
class commands_CompilePhptal extends c_ChangescriptCommand
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
		return "ctal";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "compile PHPTal extensions";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile PHPTal extensions ==");
		
		$this->loadFramework();
		PHPTALService::getInstance()->compileAttributes();
		
		$this->executeCommand("clear-webapp-cache");

		$this->quitOk("PHPTal extensions compiled successfully");
	}
}
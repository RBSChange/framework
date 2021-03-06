<?php
class commands_CompileJsDependencies extends commands_AbstractChangeCommand
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
		return "cjs";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "compile javascript dependencies files";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile javascript dependencies ==");
		
		$this->loadFramework();
		JsService::getInstance()->compileScriptDependencies();

		$this->quitOk("Javascript dependencies compiled successfully");
	}
}
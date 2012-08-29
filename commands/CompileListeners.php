<?php
class commands_CompileListeners extends c_ChangescriptCommand
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
		return "clisten";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "compile listeners";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile listeners ==");
		
		$this->loadFramework();
		$generator  = new builder_ListenersGenerator();
		$generator->generateListenerLoader();
		
		$this->quitOk("Listeners compiled successfully");
	}
}
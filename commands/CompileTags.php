<?php
class commands_CompileTags extends c_ChangescriptCommand
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
		return "ctags";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "compile tag files";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile tags ==");
		
		$this->loadFramework();
		$ts = TagService::getInstance();
		$ts->regenerateTags();
		
		$this->executeCommand("clear-webapp-cache");
		
		$this->quitOk("Tags compiled successfully");
	}
}
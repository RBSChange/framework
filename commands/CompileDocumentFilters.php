<?php
class commands_CompileDocumentFilters extends c_ChangescriptCommand
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
		return "cdf";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "compile document filters";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile document filters ==");
		
		$this->loadFramework();
		f_persistentdocument_DocumentFilterService::getInstance()->compileFilters();
		
		$this->quitOk("Document filters compiled");
	}
}
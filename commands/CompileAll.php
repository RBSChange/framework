<?php
class commands_CompileAll extends c_ChangescriptCommand
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
		return "ca";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "compile all files that need to be compiled";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile all ==");	
		$this->executeCommand("compile-config");
		
		$this->loadFramework();
		$this->executeCommand("compile-documents");
		$this->executeCommand("compile-db-schema");
		$this->executeCommand("compile-document-filters");
		$this->executeCommand("compile-roles");
		$this->executeCommand("compile-locales");
		$this->executeCommand("compile-tags");
		$this->executeCommand("compile-listeners");
		$this->executeCommand("compile-phptal");
						
		$this->quitOk("All files were compiled");
	}
}
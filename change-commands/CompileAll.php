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
		
		$parent = $this->getParent();
		$parent->executeCommand("compile-config");
		$parent->executeCommand("compile-documents");
		$parent->executeCommand("compile-aop");
		$parent->executeCommand("compile-document-filters");
		$parent->executeCommand("compile-roles");
		$parent->executeCommand("compile-permissions");
		$parent->executeCommand("compile-locales");
		$parent->executeCommand("compile-tags");
		$parent->executeCommand("compile-listeners");
		$parent->executeCommand("compile-js-dependencies");
		$parent->executeCommand("compile-phptal");
		$parent->executeCommand("compile-db-schema");
		
		$this->quitOk("All files were compiled");
	}
}
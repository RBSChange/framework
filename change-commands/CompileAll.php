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
		$parent->executeCommand("compileConfig");
		$parent->executeCommand("compileDocuments");
		$parent->executeCommand("compileDocumentFilters");
		$parent->executeCommand("compileRoles");
		$parent->executeCommand("compilePermissions");
		$parent->executeCommand("compileLocales");
		$parent->executeCommand("compileTags");
		$parent->executeCommand("compileListeners");
		$parent->executeCommand("compileJsDependencies");
		$parent->executeCommand("compilePhptal");
		$parent->executeCommand("compileDbSchema");
		
		$this->quitOk("All files were compiled");
	}
}
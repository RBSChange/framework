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
		$this->executeCommand("compileConfig");
		
		$this->loadFramework();
		$this->executeCommand("compileDocuments");
		$this->executeCommand("compileDocumentFilters");
		$this->executeCommand("compileRoles");
		$this->executeCommand("compilePermissions");
		$this->executeCommand("compileLocales");
		$this->executeCommand("compileTags");
		$this->executeCommand("compileListeners");
		$this->executeCommand("compileJsDependencies");
		$this->executeCommand("compilePhptal");
		$this->executeCommand("compileDbSchema");		
		$this->quitOk("All files were compiled");
	}
}
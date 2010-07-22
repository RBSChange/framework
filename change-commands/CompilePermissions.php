<?php
class commands_CompilePermissions extends commands_AbstractChangeCommand
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
		return "cperm";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "compile user permissions";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile permissions ==");
		
		$this->loadFramework();
		f_persistentdocument_PersistentProvider::getInstance()->clearAllPermissions();
		$ps = f_permission_PermissionService::getInstance();
		$ps->compileAllPermissions();
		
		$this->quitOk("Permissions compiled");
	}
}
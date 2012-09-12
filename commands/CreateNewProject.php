<?php
class commands_CreateNewProject extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "";
	}
	
	/**
	 * @return string
	 */
	function getDescription()
	{
		return "Create a new project";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		if (!file_exists(f_util_FileUtils::buildProjectPath('config/project.xml'))
			|| !file_exists(f_util_FileUtils::buildProjectPath('change.properties')))
		{
			$this->quitError('You have no project configuration. Please create your config/project.xml and change.properties files');
		}
		
		$this->message("== Create new project ==");
		
		$this->executeCommand("init");
		$this->executeCommand("generate-database");
		$this->executeCommand("compile-all");
		$this->executeCommand("import-init-data");
		$this->executeCommand("init-patch-db");
		f_util_System::execScript('changecron.php');
		$this->quitOk("Project is ready to use");
	}
}
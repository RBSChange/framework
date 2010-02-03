<?php
class commands_CompileConfig extends commands_AbstractChangeCommand
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
		return "cconf";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "compile the change configuration";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile config ==");
		
		$projectParser = new config_ProjectParser();
		$projectParser->execute($this->getComputedDeps());
		
		$this->warnMessage("Warn: some targets can depend on the configuration file. Please run the target that depends on
if AG_LOGGING_LEVEL was modified please execute compile-js-dependencies.
Warn: if AG_SUPPORTED_LANGUAGES was updated, please execute change generate-database to update database structure.
Warn: if you changed CHANGE_USE_CORRECTION or CHANGE_USE_WORKFLOW, please run change compile-documents.");

		if (defined("FRAMEWORK_HOME"))
		{
			// Framework is loaded and configuration may have changed
			Framework::reloadConfiguration(PROFILE);
		}
		
		$this->quitOk("Config compiled");
	}
}

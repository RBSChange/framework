<?php
class commands_CompileRoles extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "[--verbose]";
	}
	
	function getAlias()
	{
		return "croles";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "compile user roles";
	}
	
	function getOptions()
	{
		return array('verbose');
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile roles ==");
		
		$this->loadFramework();
		$securityGenerator = new builder_SecurityGenerator();
		$securityGenerator->setQuiet(!isset($options["verbose"]));
		$logs = $securityGenerator->buildSecurity();
		foreach ($logs as $log)
		{
			$this->message($log);
		}
		
		$this->quitOk("Roles compiled successfully");
	}
}
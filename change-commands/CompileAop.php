<?php
class commands_CompileAop extends commands_AbstractChangeCommand
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
		return "caop";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "compile AOP files";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile AOP ==");
		
		$this->loadFramework();
		if (Framework::inDevelopmentMode())
		{
			f_util_FileUtils::rmdir(f_util_FileUtils::buildCachePath("aop"));
			$this->warnMessage("You are in developpement mode => clean cache/aop/ directory.\nIf you are in production, please change your configuration to set AG_DEVELOPMENT_MODE to false (then re-execute change compile-aop) !\n");
			return;
		}
		ClassResolver::getInstance()->compileAOP();
		
		$this->quitOk("AOP compiled");
	}
}
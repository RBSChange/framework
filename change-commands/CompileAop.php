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
		if (class_exists('change_ConfigurationService') && change_ConfigurationService::getInstance()->isCompiled())
		{
			$this->message("== Compile AOP ==");
			$this->loadFramework();
			ClassResolver::getInstance()->compileAOP();
			return $this->quitOk("AOP compiled");
		}	
		$this->warnMessage('Config is not compiled');
		return $this->quitOk("AOP NOT compiled");
	}
}
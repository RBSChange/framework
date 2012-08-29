<?php
class commands_CompileInjection extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "";
	}
	
	function getAlias()
	{
		return "cinj";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "compile injection of classes and documents";
	}
	
	/**
	 * @see c_ChangescriptCommand::getEvents()
	 */
	public function getEvents()
	{
		return array(
			array('target' => 'compile-autoload'),
			array('target' => 'compile-config'),
			array('target' => 'compile-documents'),
		);
	}	

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile Injection ==");
		$this->loadFramework();
		change_InjectionService::getInstance()->restore();
		change_InjectionService::getInstance()->compile();
		$this->quitOk("Injection compiled");
	}
}
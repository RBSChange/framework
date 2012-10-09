<?php
class commands_CompileConfig extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	public function getUsage()
	{
		return "";
	}
	
	public function getAlias()
	{
		return "cconf";
	}
	
	/**
	 * @return string
	 */
	public function getDescription()
	{
		return "compile the change configuration";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		$this->message("== Compile config ==");
		$this->getBootStrap()->cleanDependenciesCache();
		
		require_once PROJECT_HOME . '/Change/Application.php';
		\Change\Application::getInstance()->registerNamespaceAutoload();
		
		$generator = new \Change\Configuration\Generator();
		$generator->compile($this->getComputedDeps());

		// TODO: 
		// - website.compile-js-dependencies on LOGGING_LEVEL changed
		// - uixul.compile-editors-config on SUPPORTED_LANGUAGES changed
		// - compile-documents on CHANGE_USE_CORRECTION | CHANGE_USE_WORKFLOW changed
		// - clear-webapp-cache on DEVELOPMENT_MODE changed
		// - website.compile-htaccess

		$this->quitOk("Config compiled");
	}
}
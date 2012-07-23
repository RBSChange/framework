<?php
/**
 * @deprecated all methods is in change_ConfigurationService
 * @package framework.config
 * Project parser is used to converted project.XX.xml file in php file useable by the framework
 */
class config_ProjectParser
{
	
	/**
	 * @deprecated use change_ConfigurationService::projectParserExecute()
	 * Merge specific config file of project with defulat config file and save config file in cache/config
	 * @return array old and current configuration
	 */
	public function execute($computedDeps)
	{
		return change_ConfigurationService::getInstance()->compile($computedDeps);
	}

	/**
	 * @deprecated use change_ConfigurationService::addProjectConfigurationEntry()
	 * @param string $path
	 * @param string $value
	 * @return string old value
	 */
	public static function addProjectConfigurationEntry($path, $value)
	{
		return change_ConfigurationService::getInstance()->addProjectConfigurationEntry($path, $value);
	}
	
	/**
	 * @deprecated use change_ConfigurationService::addProjectConfigurationNamedEntry()
	 * @param string $path
	 * @param string $value
	 * @return string old value
	 */
	public static function addProjectConfigurationNamedEntry($path, $entryName, $value)
	{
		return change_ConfigurationService::getInstance()->addProjectConfigurationNamedEntry($path, $entryName, $value);
	}
}

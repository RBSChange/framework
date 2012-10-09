<?php
/**
 * @deprecated use \Change\Configuration\Configuration
 */
class change_ConfigurationService extends change_BaseService
{
	/**
	 * @deprecated use \Change\Configuration\Configuration::getEntry()
	 */
	public function getConfiguration($path, $strict = true)
	{
		$configuration = \Change\Application::getInstance()->getConfiguration();
		if (!$configuration->hasEntry($path))
		{
			if ($strict)
			{
				f_util_ProcessUtils::printBackTrace();
				echo PHP_EOL, 'No configuration entry for ', $path, PHP_EOL, PHP_EOL;
				throw new \Exception('No configuration entry for ' . $path);
			}
			return false;
		}
		return $configuration->getEntry($path);
	}
	
	/**
	 * @deprecated use \Change\Configuration\Configuration::getEntry()
	 */
	public function getConfigurationValue($path, $defaultValue = null)
	{
		return \Change\Application::getInstance()->getConfiguration()->getEntry($path, $defaultValue);
	}
	
	/**
	 * @deprecated use \Change\Configuration\Configuration::getConfigArray()
	 */
	public function getAllConfiguration()
	{
		return \Change\Application::getInstance()->getConfiguration()->getConfigArray();
	}

	/**
	 * @deprecated use \Change\Configuration\Configuration::hasEntry()
	 */
	public function hasConfiguration($path)
	{
		return \Change\Application::getInstance()->getConfiguration()->hasEntry($path);
	}
	
	/**
	 * @deprecated use \Change\Configuration\Configuration::addVolatileEntry()
	 */
	public function addVolatileProjectConfigurationNamedEntry($path, $value)
	{
		return \Change\Application::getInstance()->getConfiguration()->addVolatileEntry($path, $value);
	}
	
	/**
	 * @deprecated use \Change\Configuration\Configuration::addPersistentEntry()
	 */
	public function addProjectConfigurationEntry($path, $value)
	{
		$sections = array();
		foreach (explode('/', $path) as $name)
		{
			if (trim($name) != '')
			{
				$sections[] = trim($name);
			}
		}
		if (count($sections) < 2 && $this->addVolatileProjectConfigurationNamedEntry($path, $value))
		{
			return false;
		}
		$entryName = array_pop($sections);
		return \Change\Application::getInstance()->getConfiguration()->addPersistentEntry(implode('/', $sections), $entryName, $value);
	}
	
	/**
	 * @deprecated use \Change\Configuration\Configuration::addPersistentEntry()
	 */
	public function addProjectConfigurationNamedEntry($path, $entryName, $value)
	{
		return \Change\Application::getInstance()->getConfiguration()->addPersistentEntry($path, $entryName, $value);
	}
	
	/**
	 * @deprecated use \Change\Application::getProfile()
	 */
	public function getCurrentProfile()
	{
		return \Change\Application::getInstance()->getProfile();
	}
}
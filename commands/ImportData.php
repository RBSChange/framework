<?php
class commands_ImportData extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "<moduleName> <scriptName>";
	}

	function getAlias()
	{
		return "id";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "import data";
	}

	/**
	 * @param integer $completeParamCount the parameters that are already complete in the command line
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return string[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 0)
		{
			$components = array();
			foreach ($this->getBootStrap()->getProjectDependencies() as $package) 
			{
				/* @var $package c_Package */
				if ($package->isModule() || $package->isTheme())
				{
					if (count($this->getXmlFileNames($package->getPath() . '/setup')))
					{
						$components[] = $package->getKey();
					}
				}
			}
			return $components;
		}
		elseif ($completeParamCount == 1)
		{
			$package = $this->getPackageByName($params[0]);
			if ($package->isValid())
			{
				return $this->getXmlFileNames($package->getPath() . '/setup');
			}
		}
		return null;
	}
	
	private function getXmlFileNames($path)
	{
		$names = array();
		if (is_dir($path))
		{
			foreach (scandir($path) as $pathfile) 
			{
				if (substr($pathfile, -4) === '.xml')
				{
					$n = basename($pathfile);
					if (!in_array($n, array('init.xml', 'useractionlogger.xml')))
					{
						$names[] = basename($pathfile);
					}
				}
			}
		}
		return $names;
	}
	
	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		if (count($params) == 2)
		{
			$package = $this->getPackageByName($params[0]);
			if ($package->isValid() && ($package->isModule() || $package->isTheme()))
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$package = $this->getPackageByName($params[0]);
		$scriptName = $params[1];
		$this->message("== Import data ".$package->getKey()." : $scriptName ==");

		$this->loadFramework();

		$scriptReader = import_ScriptReader::getInstance();
		if ($package->isModule())
		{
			$scriptReader->executeModuleScript($package->getName(), $scriptName);
		}
		else
		{
			$scriptReader->executeThemeScript($package->getName(), $scriptName);
		}
	
		$this->quitOk($package->getKey() . ": " . $scriptName ." imported");
	}
}
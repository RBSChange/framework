<?php
class commands_ImportData extends commands_AbstractChangeCommand
{
	/**
	 * @return String
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
	 * @return String
	 */
	function getDescription()
	{
		return "import data";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 2;
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
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
					if (is_dir($package->getPath() . '/setup'))
					{
						if (count($this->getXmlFileNames($package->getPath() . '/setup')))
						{
							$components[] = $package->getName();
						}
					}
				}
			}
			return $components;
		}
		elseif ($completeParamCount == 1)
		{
			$scripts = array();
			$moduleName = $params[0];
			foreach (glob("modules/$moduleName/setup/*.xml") as $script)
			{
				$scripts[] = basename($script);
			}
			return $scripts;
		}
		return null;
	}
	
	private function getXmlFileNames($path)
	{
		$names = array();
		foreach (scandir($path) as $pathfile) 
		{
			if (substr($pathfile, -4) === '.xml')
			{
				$names = basename($pathfile);
			}
		}
		return $names;
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$moduleName = $params[0];
		$scriptName = $params[1];
		$this->message("== Import data $moduleName/$scriptName ==");

		$this->loadFramework();

		$scriptReader = import_ScriptReader::getInstance();
		$scriptReader->executeModuleScript($moduleName, $scriptName);

		$this->quitOk("$moduleName/$scriptName imported");
	}
}
<?php
class commands_CompileLocales extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "[package1 package2 ... packageN]";
	}

	function getAlias()
	{
		return "ci18n";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "compile locale files";
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		$components = array();
		foreach ($this->getBootStrap()->getProjectDependencies() as $p) 
		{
			/* @var $p c_Package */
			if (is_dir(f_util_FileUtils::buildPath($p->getPath(), 'i18n')))
			{
				$components[] = $p->getKey();
			}
		}	
		return array_diff($components, $params);
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile locales ==");
		$this->loadFramework();

		$ls = LocaleService::getInstance();
		if ( f_util_ArrayUtils::isEmpty($params))
		{
			$ls->regenerateLocales();
			$this->executeCommand('clear-webapp-cache');
			$this->executeCommand('clear-template-cache');
			return $this->quitOk('All locales compiled successfully.');
		}

		foreach ($params as $componentName)
		{
			$package = $this->getPackageByName($componentName);
			if (!$package->isValid() || !$package->isInProject()) 
			{
				$this->errorMessage("Invalid package: " . $package->getName());
				continue;
			}
			elseif ($package->isFramework())
			{
				$ls->regenerateLocalesForFramework();
				$this->okMessage('Framework compiled');
			}
			elseif ($package->isTheme())
			{
				$ls->regenerateLocalesForTheme($package->getName());
				$this->message("Theme ".$package->getName()." compiled");
			}
			elseif ($package->isModule())
			{
				if (is_dir(f_util_FileUtils::buildPath($package->getPath(), 'i18n')))
				{
					$ls->regenerateLocalesForModule($componentName);
					$this->message("Module ".$package->getName()." compiled");
				}
				else
				{
					$this->warnMessage("Module " . $package->getName() ." has no locale.");
				}
			}
			else
			{
				$this->errorMessage("Unknow package: " . $package->getKey());
			}
		}

		$this->executeCommand('clear-webapp-cache');
		$this->executeCommand('clear-template-cache');
		
		if ($this->hasError())
		{
			return $this->quitError("All locales could not be compiled: ".$this->getErrorCount()." errors");
		}
		
		return $this->quitOk("Locales successfully compiled");
	}
}
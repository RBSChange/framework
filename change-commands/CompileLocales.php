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
	function getParameters($completeParamCount, $params, $options)
	{
		$components = array();
		if (is_dir("framework"))
		{
			$components[] = "framework";
		}
		foreach (glob("modules/*", GLOB_ONLYDIR) as $module)
		{
			$components[] = basename($module);
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
			$this->getParent()->executeCommand('clear-webapp-cache');
			return $this->quitOk('All locales compiled successfully.');
		}

		foreach ($params as $componentName)
		{
			if ($componentName == 'framework')
			{
				$ls->regenerateLocalesForFramework();
				$this->okMessage('Framework locales compiled');
			}
			else
			{
				$package = "modules_".$componentName;
				$ms = ModuleService::getInstance();
				if ( in_array($package, $ms->getModules()) )
				{
					$ls->regenerateLocalesForModule($package);
					$this->message("$componentName module locales compiled");
				}
				else
				{
					$this->errorMessage("Module $componentName does not exist.");
				}
			}
		}
			
		if ($this->hasError())
		{
			return $this->quitError("All locales could not be compiled: ".$this->getErrorCount()." errors");
		}
		$this->getParent()->executeCommand('clear-webapp-cache');
		$this->getParent()->executeCommand('clear-template-cache');
		return $this->quitOk("Locales successfully compiled");
	}
}
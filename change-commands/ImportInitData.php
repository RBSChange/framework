<?php
class commands_ImportInitData extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "[moduleName1 moduleName2 ... moduleNameN [--force]]";
	}

	function getAlias()
	{
		return "iid";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Import init data from modules";
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
			$modules = array();
			foreach (glob("modules/*/setup", GLOB_ONLYDIR) as $path)
			{
				$modules[] = basename(dirname($path));
			}
			return $modules;
		}
		return null;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Import init data ==");	
		$this->loadFramework();

		$ids = InitDataService::getInstance();
		$ids->setLogger($this);

		if (!f_util_ArrayUtils::isEmpty($params))
		{
			foreach ($params as $moduleName)
			{
				$packageName = 'modules_'.$moduleName;
				
				$importedDate = $ids->getImportedDate($packageName);
				if ($importedDate === null)
				{
					$ids->import($packageName);
				}
				elseif (isset($options['force']))
				{
					$this->log('Re-initialize Package ' . $packageName . ' initialized on ' . $importedDate);
					$ids->clearImportedDate($packageName);
					$ids->import($packageName);
				}
				else
				{
					$this->log('Package ' . $packageName . ' initialized on ' . $importedDate);
				}
			}
		}
		else
		{
			$packageNames = ModuleService::getInstance()->getPackageNames();
			foreach ($packageNames as $packageName)
			{
				$importedDate = $ids->getImportedDate($packageName);
				if ($importedDate === null)
				{
					$moduleName = ModuleService::getInstance()->getShortModuleName($packageName);
					$this->executeCommand('import-init-data', array($moduleName));
				}
				else
				{
					$this->log('Package ' . $packageName . ' initialized on ' . $importedDate);
				}
			}
		}
		$this->quitOk("Init data imported");
	}
}
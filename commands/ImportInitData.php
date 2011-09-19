<?php
class commands_ImportInitData extends c_ChangescriptCommand
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

	function getOptions()
	{
		return array('force');
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
		$components = array();
		foreach ($this->getBootStrap()->getProjectDependencies() as $package) 
		{
			/* @var $package c_Package */
			if ($package->isModule() && is_readable(f_util_FileUtils::buildPath($package->getPath(), 'setup', 'initData.php')))
			{
				$components[] = $package->getKey();
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
		$this->message("== Import init data ==");	
		$this->loadFramework();

		$ids = InitDataService::getInstance();
		$ids->setLogger($this);

		if (!f_util_ArrayUtils::isEmpty($params))
		{
			foreach ($params as $componentName)
			{
				$package = $this->getPackageByName($componentName);
				if ($package->isModule() && $package->isInProject())
				{
					$packageName = 'modules_'.$package->getName();					
					$importedDate = $ids->getImportedDate($packageName);
					if ($importedDate === null)
					{
						$ids->import($packageName);
					}
					elseif (isset($options['force']))
					{
						$this->log('Re-initialize Module ' . $package->getName() . ' initialized on ' . $importedDate);
						$ids->clearImportedDate($packageName);
						$ids->import($packageName);
					}
					else
					{
						$this->log('Module ' . $package->getName() . ' initialized on ' . $importedDate);
					}
				}
			}
		}
		else
		{
			foreach ($this->getBootStrap()->getProjectDependencies() as $package) 
			{
				/* @var $package c_Package */
				if ($package->isModule())
				{
					$packageName = 'modules_'.$package->getName();
					$importedDate = $ids->getImportedDate($packageName);
					if ($importedDate === null)
					{
						$this->executeCommand('import-init-data', array($package->getKey()));
					}
					else
					{
						$this->log('Module ' . $package->getName() . ' initialized on ' . $importedDate);
					}
				}
			}
		}
		$this->quitOk("Init data imported");
	}
}
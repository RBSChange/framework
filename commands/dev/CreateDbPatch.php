<?php
class commands_CreateDbPatch extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "<moduleName|framework>";
	}

	function getAlias()
	{
		return "cdbp";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "Creates a new DB patch";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		if (count($params) == 1)
		{
			$type = $params[0] === 'framework' ? null : 'modules';
			$package = c_Package::getNewInstance($type, $params[0], PROJECT_HOME);
			$packages = $this->getBootStrap()->getProjectDependencies();
			if (!isset($packages[$package->getKey()]))
			{
				$this->errorMessage('invalid param value: ' . $params[0]);
				return false;
			}
			return true;
		}
		return false;
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
				if ($package->isFramework() || $package->isModule())
				{
					$components[] = $package->getName();
				}
			}
			return $components;
		}
		return null;
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Create DB patch ==");
		$this->loadFramework();
		$componentName = $params[0];
		
		if ($componentName !== "framework" && $componentName != "webapp" && !ModuleService::getInstance()->moduleExists($componentName))
		{
			return $this->quitError("Component $componentName does not exits");
		}
	
		$patchFolder = PatchService::getInstance()->createDBPatch($componentName);	
		$patchNumber = basename($patchFolder);
		
		$this->log("Please now edit $patchFolder/install.php and $patchFolder/README.");		
		return $this->quitOk("DB patch $componentName $patchNumber successfully created");
	}
}
<?php
class commands_AddAction extends c_ChangescriptCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<moduleName> <actionName> [--json]";
	}

	/**
	 * @see c_ChangescriptCommand::getOptions()
	 */
	public function getOptions()
	{
		return array('json');
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "add an action";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		if (count($params) == 2)
		{
			$package = $this->getPackageByName($params[0]);
			if ($package->isModule() && $package->isInProject())
			{
				if (preg_match('/^[a-z][a-z0-9]{1,50}+$/i', $params[1]))
				{
					return true;
				}
				else
				{
					$this->errorMessage('Invalid action name: ' . $params[1]);
				}
			}
			else
			{
				$this->errorMessage('Invalid module name: ' . $params[0]);
			}
		}
		return false;
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
				if ($package->isModule())
				{
					$components[] = $package->getName();
				}
			}
			return $components;
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
		$package = $this->getPackageByName($params[0]);
		$moduleName = $package->getName();
		$actionName = ucfirst($params[1]);
		if (substr($actionName, -6) === 'Action')
		{
			$actionName = substr($actionName,0, -6);
		}
		$this->message("== Add action $actionName ==");
		
		$this->loadFramework();
		$actionFilename = $actionName.'Action.class.php';
		
		$modulePath = f_util_FileUtils::buildPath($package->getPath(), 'actions', $actionFilename);
		$overrideModulePath = f_util_FileUtils::buildOverridePath($package->getType(), $package->getName(), 'actions', $actionFilename);
		if (file_exists($modulePath) || file_exists($overrideModulePath))
		{
			return $this->quitError('Action "'.$actionName.'" already exists in module '.$moduleName.'".');
		}
		
		$rd = $this->getBootStrap()->inReleaseDevelopement();
		$override = !$rd && !$package->isStandalone();
		
		$actionPath = $override ? $overrideModulePath : $modulePath;
		f_util_FileUtils::mkdir(dirname($actionPath));
		

		$moduleGenerator = new builder_ModuleGenerator($moduleName);
		if (isset($options['json']))
		{
			$result = $moduleGenerator->generateJSONAction($actionName);
		}
		else
		{
			$result = $moduleGenerator->generateFrontAction($actionName);
		}
		
					
		f_util_FileUtils::write($actionPath, $result);
		$class = $moduleName.'_'.$actionName . 'Action';
		ClassResolver::getInstance()->appendToAutoloadFile($class, $actionPath);
		$this->quitOk("Action $actionName added in module $moduleName Please now edit $actionPath.");
	}
}
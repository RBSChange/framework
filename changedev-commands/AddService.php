<?php
class commands_AddService extends c_ChangescriptCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<moduleName> <serviceName>";
	}
	
	/**
	 * @return String
	 */
	function getDescription()
	{
		return "add a service";
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
					$this->errorMessage('Invalid service class name: ' . $params[1]);
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
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Add service ==");
		$this->loadFramework();
		$package = $this->getPackageByName($params[0]);
		
		$moduleName = $package->getName();
		$serviceName = ucfirst($params[1]);
		if (substr($serviceName, -7) === 'Service')
		{
			$serviceName = substr($serviceName,0, -7);
		}
			
		$serviceFile = f_util_FileUtils::buildPath($package->getPath(), 'lib', 'services', $serviceName.'Service.class.php');
		if (file_exists($serviceFile))
		{
			return $this->quitError('Service "'.$serviceName.'" already exists in module "'.$moduleName.'" (check '.$serviceFile.').');
		}
		
		$moduleGenerator = new builder_ModuleGenerator($moduleName);
		$result = $moduleGenerator->generateService($serviceName, $moduleName);

		f_util_FileUtils::writeAndCreateContainer($serviceFile, $result);
		
		$class = $moduleName.'_'.$serviceName . 'Service';
		ClassResolver::getInstance()->appendToAutoloadFile($class, realpath($serviceFile));
		return $this->quitOk("Service $serviceName added in module $moduleName Please now edit $serviceFile.");
	}
}
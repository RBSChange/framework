<?php
class commands_AddService extends commands_AbstractChangedevCommand
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
	function getParameters($completeParamCount, $params, $options)
	{
		if ($completeParamCount == 0)
		{
			$components = array();
			foreach (glob("modules/*", GLOB_ONLYDIR) as $module)
			{
				$components[] = basename($module);
			}
			return $components;
		}
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Add service ==");

		$moduleName = $params[0];
		$serviceName = $param[1];
		
		$this->loadFramework();

		$serviceFile = $this->getServicePath($moduleName, $serviceName);
		if(file_exists($serviceFile))
		{
			return $this->quitError('Service "'.$serviceName.'" already exists in module "'.$moduleName.'" (check '.$serviceFile.').');
		}

		$serviceFile = 'modules/'.$moduleName.'/lib/services/'.$serviceName.'Service.class.php';
		$moduleGenerator = new builder_ModuleGenerator($moduleName);
		$moduleGenerator->setAuthor($this->getAuthor());
		$result = $moduleGenerator->generateService($serviceName, $moduleName);

		f_util_FileUtils::write($serviceFile, $result);
		$class = $moduleName.'_'.$serviceName . 'Service';
		ClassResolver::getInstance()->appendToAutoloadFile($class, realpath($serviceFile));
		$this->quitOk("Service $serviceName added in module $moduleName
Please now edit $serviceFile.");
	}

	private function getServicePath($moduleName, $serviceName)
	{
		return FileResolver::getInstance()->setPackageName('modules_'.$moduleName)->setDirectory('lib/services')->getPath($serviceName.'Service.class.php');
	}
}
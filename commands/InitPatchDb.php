<?php
class commands_InitPatchDb extends c_ChangescriptCommand
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
		return "ipdb";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "init patch DB";
	}
	
	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return boolean
	 */
	protected function validateArgs($params, $options)
	{
		if (count($params) === 0)
		{
			return true;
		}
		elseif (count($params) === 1)
		{
			$type = $params[0] === 'framework' ? null : 'modules'; 
			$package = c_Package::getNewInstance($type, $params[0], PROJECT_HOME);
			
			if (!$package->isInProject())
			{
				$this->errorMessage('Invalid package name: ' . $params[0]);
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
			$parameters = array();
			foreach ($this->getBootStrap()->getProjectDependencies() as $package) 
			{
				/* @var $package c_Package */
				if (is_readable(f_util_FileUtils::buildPath($package->getPath(), 'patch', 'lastpatch')))
				{
					$parameters[] = $package->getName();
				}
			}
			return $parameters;
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
		$this->message("== Init patch DB ==");
		$this->loadFramework();
		$componentName = isset($params[0]) ? $params[0] : null; 
		PatchService::getInstance()->updateRepository($componentName);	
		$this->quitOk('Patch repository successfully updated');
	}
}
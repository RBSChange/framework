<?php
class commands_PatchInfo extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<moduleName|framework> <patchNumber>";
	}
	
	function getAlias()
	{
		return "pi";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Displays informations about a patch";
	}
	
	
	
	/**
	 * @see c_ChangescriptCommand::validateArgs()
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
				if (($package->isFramework() || $package->isModule()) && is_dir(f_util_FileUtils::buildPath($package->getPath(), 'patch')))
				{
					$components[] = $package->getName();
				}
			}
			return $components;
		}
		elseif ($completeParamCount == 1)
		{
			$patches = PatchService::getInstance()->getPatchList($params[0]);
			if (count($patches)) 
			{
				return $patches;
			}
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
		$this->message("== Patch information ==");
		$this->loadFramework();	
		$moduleName = $params[0];
		$patchNumber = $params[1];
		$this->message(PatchService::getInstance()->patchInfo($moduleName, $patchNumber));
	}
}
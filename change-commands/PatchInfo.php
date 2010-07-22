<?php
class commands_PatchInfo extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<componentName> <patchNumber>";
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
			foreach (glob("modules/*/patch/*", GLOB_ONLYDIR) as $patch)
			{
				$components[] = basename(dirname(dirname($patch)));
			}
			return array_unique($components);
		}
		elseif ($completeParamCount == 1)
		{
			$patches = array();
			$moduleName = $params[0];
			foreach (glob("modules/$moduleName/patch/*", GLOB_ONLYDIR) as $patch)
			{
				$patches[] = basename($patch);
			}
			return $patches;
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
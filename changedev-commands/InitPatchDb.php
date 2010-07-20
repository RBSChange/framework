<?php
class commands_InitPatchDb extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "[component]";
	}
	
	function getAlias()
	{
		return "ipdb";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "init patch DB";
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
			$components = array("framework", "webapp");
			$components = array_merge($components, ModuleService::getInstance()->getModules());
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
		$this->message("== Init patch DB ==");
		$this->loadFramework();

		$targetPackage = isset($params[0]) ? $params[0] : null; 
		PatchService::getInstance()->updateRepository($targetPackage);
		
		$this->quitOk('Patch repository successfully updated');
	}
}
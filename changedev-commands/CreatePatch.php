<?php
class commands_CreatePatch extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<moduleName>";
	}

	function getAlias()
	{
		return "cp";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Creates a new patch";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 1;
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
			if (is_dir("framework"))
			{
				$components[] = "framework";
			}
			if (is_dir("webapp"))
			{
				$components[] = "webapp";
			}
			foreach (glob("modules/*", GLOB_ONLYDIR) as $module)
			{
				$components[] = basename($module);
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
		$this->message("== Create patch ==");

		$this->loadFramework();
		$componentName = $params[0];
		if ($componentName !== "framework" && $componentName != "webapp" && !ModuleService::getInstance()->moduleExists($componentName))
		{
			return $this->quitError("Component $componentName does not exits");
		}
		$patchFolder = patch_BasePatch::createNewPatch($componentName, $this->getAuthor());

		return $this->quitOk("Patch $patchFolder successfully created\nPlease now edit $patchFolder/install.php and $patchFolder/README.");
	}
}
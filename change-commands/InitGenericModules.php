<?php
class commands_InitGenericModules extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}

	function getAlias()
	{
		return "igm";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "init generic modules";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Init generic modules ==");
		
		$this->loadFramework();
		$computedDeps = $this->getComputedDeps();
		// Create module links
		foreach ($computedDeps["module"] as $moduleName => $moduleInfo)
		{
			$this->message("Symlink $moduleName-".$moduleInfo["version"]);
			if (f_util_FileUtils::symlink($moduleInfo["path"], "modules/$moduleName", f_util_FileUtils::OVERRIDE))
			{
				$appendAutoload = false;
				// we created a new entry
				if (is_dir("modules/$moduleName/change-commands"))
				{
					$this->getParent()->addCommandDir("modules/$moduleName/change-commands", "$moduleName|Module $moduleName commands");
					$appendAutoload = true;	
				}
				if (is_dir("modules/$moduleName/changedev-commands"))
				{
					$this->getParent()->addGhostCommandDir("modules/$moduleName/changedev-commands", "$moduleName|Module $moduleName commands", $appendAutoload);
					$appendAutoload = true;
				}
				if ($appendAutoload)
				{
					$this->getParent()->appendToAutoload("modules/$moduleName");
				}
				ClassResolver::getInstance()->appendDir(realpath("modules/$moduleName"));
			}
		}

		$this->getParent()->executeCommand("compileConfig");

		$this->quitOk("Generic modules initiated");
	}
}

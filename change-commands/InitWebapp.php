<?php
class commands_InitWebapp extends commands_AbstractChangeCommand
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
		return "iw";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "init webapp folder";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Init webapp ==");

		$this->getParent()->executeCommand("compileConfig");

		// Copy files
		$this->loadFramework();

		$exclude = array(".svn");
		$webapp = f_util_FileUtils::buildWebappPath();

		$this->message("Import framework webapp files");
		$frameworkWebapp = f_util_FileUtils::buildWebeditPath("framework", "builder", "webapp");
		f_util_FileUtils::cp($frameworkWebapp, $webapp, f_util_FileUtils::OVERRIDE | f_util_FileUtils::APPEND, $exclude);
		$this->message("Create webapp/www/media folder");
		f_util_FileUtils::symlink(f_util_FileUtils::buildWebappPath("media"), f_util_FileUtils::buildWebappPath("www/media"), f_util_FileUtils::OVERRIDE);
		$this->message("Create webapp/www/publicmedia folder");
		f_util_FileUtils::symlink(f_util_FileUtils::buildWebeditPath("media"), f_util_FileUtils::buildWebappPath("www/publicmedia"), f_util_FileUtils::OVERRIDE);

		// Icons symlink
		if (file_exists(WEBEDIT_HOME."/libs/icons"))
		{
			$this->message("Create icons symlink");
			$iconsLink = f_util_FileUtils::buildWebappPath("media", "icons");
			f_util_FileUtils::symlink(WEBEDIT_HOME."/libs/icons", $iconsLink, f_util_FileUtils::OVERRIDE);
		}
		elseif (($computedDeps = $this->getComputedDeps()) && isset($computedDeps["lib"]["icons"]))
		{
			$this->warnMessage(WEBEDIT_HOME."/libs/icons does not exists. Did you ran init-project ?");
		}

		foreach (ModuleService::getInstance()->getModulesObj() as $module)
		{
			$moduleWebapp = $module->getPath()."/webapp";
			if (is_dir($moduleWebapp))
			{
				$this->message("Import ".$module->getName()." webapp files");
				f_util_FileUtils::cp($moduleWebapp, $webapp, f_util_FileUtils::OVERRIDE | f_util_FileUtils::APPEND, $exclude);
			}
		}



		// Apply file policy
		$this->getParent()->executeCommand("applyWebappPolicy");

		$this->quitOk("Webapp initialized");
	}
}
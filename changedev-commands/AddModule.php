<?php
class commands_AddModule extends commands_AbstractChangedevCommand
{
	/**
	 * @return String
	 */
	public function getUsage()
	{
		$usage = "<moduleName> [icon]";
		return $usage;
	}

	/**
	 * @return String
	 */
	public function getDescription()
	{
		return "add an empty module to your project.";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) >= 1;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Add module ==");

		$moduleName = $params[0];
		$icon = isset($params[1]) ? $params[1] : "package";
		
		$this->loadFramework();
		$modulePath = f_util_FileUtils::buildModulesPath($moduleName);
		if (file_exists($modulePath))
		{
			return $this->quitError("Module $moduleName already exists");
		}
		f_util_FileUtils::mkdir($modulePath);

		// Make auto generated file
		$moduleGenerator = new builder_ModuleGenerator($moduleName);
		$moduleGenerator->setAuthor($this->getAuthor());
		$moduleGenerator->setVersion(FRAMEWORK_VERSION);
		$moduleGenerator->setTitle(ucfirst($moduleName) . ' module');
		$moduleGenerator->setIcon($icon);
		$moduleGenerator->generateAllFile();
		
		$p = c_Package::getNewInstance('modules', $moduleName, PROJECT_HOME);
		$p->setDownloadURL('none');
		$p->setVersion(FRAMEWORK_VERSION);
		$this->getBootStrap()->updateProjectPackage($p);
		
		// Generate locale for new module
		LocaleService::getInstance()->regenerateLocalesForModule($moduleName);

		$this->changecmd("clear-webapp-cache");
		$this->changecmd("compile-config");
		$this->changecmd("compile-documents");
		$this->changecmd("compile-editors-config");
		$this->changecmd("compile-roles");
		 
		return $this->quitOk('Module ' . $moduleName . ' ready');
	}
}
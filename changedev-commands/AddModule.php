<?php
class commands_AddModule extends commands_AbstractChangedevCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		$usage = "<moduleName> <topic|folder> [icon]";
		return $usage;
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "add an empty module to your project, topic or folder based.";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) >= 2 && ($params[1] == "topic" || $params[1] == "folder");
	}

	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 1)
		{
			return array("topic", "folder");
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
		$this->message("== Add module ==");

		$moduleName = $params[0];
		$useTopic = ($params[1] == "topic");
		$icon = isset($params[2]) ? $params[2] : "package";
		
		$this->loadFramework();
		$modulePath = f_util_FileUtils::buildWebeditPath("modules", $moduleName);
		if (file_exists($modulePath))
		{
			return $this->quitError("Module $moduleName already exists");
		}
		f_util_FileUtils::mkdir($modulePath);

		// TODO: refactor with modulebuilder_ModuleService::generateModule($module)

		// Make auto generated file
		$moduleGenerator = new builder_ModuleGenerator($moduleName);
		$moduleGenerator->setAuthor($this->getAuthor());
		$moduleGenerator->setVersion(FRAMEWORK_VERSION);
		$moduleGenerator->setTitle(ucfirst($moduleName) . ' module');
		$moduleGenerator->setIcon($icon);
		$moduleGenerator->setUseTopic($useTopic);
		$moduleGenerator->generateAllFile();

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
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
		if (count($params) == 1 || count($params) == 2)
		{
			$moduleName = strtolower($params[0]);
			if (file_exists(f_util_FileUtils::buildWebeditPath('modules', $moduleName)))
			{
				$this->errorMessage('Module "' . $moduleName . '" already exist.');
				return false;
			}
			if (!preg_match('/^[a-z0-9]+$/', $moduleName))
			{
				$this->errorMessage('Name "' . $moduleName . '" is not valid for a module');
				return false;
			}
			return true;
		}
		elseif (count($params) > 2)
		{
			$this->errorMessage('Too many arguments.');
			return false;
		}
		return false;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Add module ==");

		$moduleName = strtolower($params[0]);
		$icon = isset($params[1]) ? $params[1] : "package";
		
		$this->loadFramework();
		f_util_FileUtils::mkdir(f_util_FileUtils::buildWebeditPath('modules', $moduleName));

		// Make auto generated file
		$moduleGenerator = new builder_ModuleGenerator($moduleName);
		$moduleGenerator->setAuthor($this->getAuthor());
		$moduleGenerator->setVersion(FRAMEWORK_VERSION);
		$moduleGenerator->setTitle(ucfirst($moduleName) . ' module');
		$moduleGenerator->setIcon($icon);
		$moduleGenerator->generateAllFile();

		// Generate locale for new module
		LocaleService::getInstance()->regenerateLocalesForModule($moduleName);

		$this->changecmd("clear-webapp-cache");
		$this->changecmd("compile-config");
		$this->changecmd("compile-roles");
		 
		return $this->quitOk('Module ' . $moduleName . ' ready');
	}
}
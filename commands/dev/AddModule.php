<?php
class commands_AddModule extends c_ChangescriptCommand
{
	/**
	 * @return String
	 */
	public function getUsage()
	{
		$usage = "<moduleName> [--icon=<iconName>] [--hidden] [--category=<e-commerce|admin>]";
		return $usage;
	}

	public function getOptions()
	{
		return array('--icon', '--hidden', '--category');
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
		if (count($params) != 1)
		{
			return false;
		}
		$moduleName = $params[0];
		if (!preg_match('/^[a-z][a-z0-9]{1,24}$/', $moduleName))
		{
			$this->errorMessage("Invalid module name ([a-z][a-z0-9]{1,24}): " . $moduleName);
			return false;
		}
		elseif (in_array($moduleName, array('framework', 'webapp')))
		{
			$this->errorMessage("Reserved module name: " . $moduleName);	
			return false;
		}

		$package = $this->getPackageByName($moduleName);
		if ($package->isInProject())
		{
			$this->errorMessage("Module $moduleName already exists");
			return false;
		}
		
		$packages = $this->getBootStrap()->getReleasePackages($this->getBootStrap()->getReleaseRepository());
		if (isset($packages[$package->getKey()]))
		{
			$this->errorMessage("Reserved standard release module name: " . $moduleName);		
			return false;
		}
		
		if (isset($options['icon']) && !is_string($options['icon']))
		{
			$this->errorMessage("Invalid icon name : " . $options['icon']);
			return false;			
		}
		if (isset($options['category']) && !in_array($options['category'], array('e-commerce', 'admin')))
		{
			$this->errorMessage("Invalid category (e-commerce, admin): " . $options['category']);
			return false;			
		}
		return true;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Add module ==");
		$p = $this->getPackageByName($params[0]);

		$icon = (isset($options['icon'])) ? $options['icon'] : 'package';
		$iconLibPath = f_util_FileUtils::buildProjectPath('libs', 'icons', 'small', $icon . '.png');
		if (!file_exists($iconLibPath))
		{
			$iconLibPath = f_util_FileUtils::buildPath($p->getPath(), 'webapp', 'changeicons', 'small', $icon . '.png');
			$this->warnMessage('Please add icon in: ' .$iconLibPath);
		}
		
		$hidden = (isset($options['hidden']) && $options['hidden'] == true);
		$category = null;
		if (isset($options['category']))
		{
			$category = $options['category'];
		}

		$this->loadFramework();
		$this->log('Create module dir: ' . $p->getPath());
		f_util_FileUtils::mkdir($p->getPath());

		// Make auto generated file
		$moduleGenerator = new builder_ModuleGenerator($p->getName());
		$moduleGenerator->setVersion(FRAMEWORK_VERSION);
		$moduleGenerator->setIcon($icon);
		$moduleGenerator->setCategory($category);
		$moduleGenerator->setVisibility(!$hidden);
		$moduleGenerator->generateAllFile();
		
		
		$p->setDownloadURL('none');
		$p->setVersion(FRAMEWORK_VERSION);
		$this->getBootStrap()->updateProjectPackage($p);
		
		// Generate locale for new module
		LocaleService::getInstance()->regenerateLocalesForModule($p->getName());

		$this->executeCommand("compile-config");
		
		if (!$hidden)
		{
			$this->executeCommand("clear-webapp-cache");
			$this->executeCommand("compile-roles");
		}
		
		return $this->quitOk('Module ' . $p->getName() . ' ready');
	}
}
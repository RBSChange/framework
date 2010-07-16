<?php
class commands_InstallModule extends commands_AbstractChangedevCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<moduleName>-<moduleVersion>";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "install a module";
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
		$bootStrap = c_ChangeBootStrap::getLastInstance();
		$remoteModules = $bootStrap->getRemoteModules(Framework::getVersion());
		$moduleService = ModuleService::getInstance();
		foreach ($remoteModules as $key => $module)
		{
			$matches = null;
			if (!preg_match('/^(.*?)-([0-9].*)$/', $module, $matches))
			{
				// this should not happen ...
				continue;
			}
			$moduleName = $matches[1];
			if (!$moduleService->moduleExists($moduleName))
			{
				continue;
			}
			$installedVersion = $moduleService->getModuleVersion($moduleName);
			$remoteVersion = $matches[2];
			if ($bootStrap->compareVersion($installedVersion, $remoteVersion) <= 0)
			{
				unset($remoteModules[$key]);
			}
		}
		return $remoteModules;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$moduleFullName = $params[0];
		$this->message("== Install module $moduleFullName ==");
		
		$matches = null;
		if (!preg_match("/^(.*?)-([0-9].*)$/", $moduleFullName, $matches))
		{
			return $this->quitError("'$moduleFullName' is not a valid component name");
		}
		
		$bootStrap = $this->getParent()->getBootStrap();
		$moduleName = $matches[1];
		$moduleVersion = $matches[2];
		
		$installedVersion = ModuleService::getInstance()->getModuleVersion("modules_".$moduleName);
		if ($installedVersion !== null)
		{
			$this->message("$moduleName module is already installed in version ".$installedVersion);
			switch ($bootStrap->compareVersion($moduleVersion, $installedVersion))
			{
				case 0:
					return $this->quitOk("Nothing to do.");
				case 1:
					return $this->quitOk("Installed version '".$installedVersion."' is newer than requested version ".$moduleVersion.".");
				case -1:
					return $this->quitError("Can not upgrade module version for now. You must use a migration script");
			}
		}
		
		$modulePath = $bootStrap->installComponent(c_ChangeBootStrap::$DEP_MODULE, $moduleName, $moduleVersion);
		list ( , $computedDeps) = $bootStrap->getDependencies($modulePath);
		
		$modulesToInstall = array();
		$libsToInstall = array();
		$pearLibsToInstall = array();
		
		$this->message("Check dependencies integrity");
		
		$projectDeps = $this->getComputedDeps();
		foreach ($computedDeps as $depType => $deps)
		{
			$depTypeStr = $bootStrap->getDepTypeAsString($depType);
			foreach ($deps as $depName => $depVersions)
			{
				if (isset($projectDeps[$depTypeStr][$depName]))
				{
					$this->debugMessage("Existing dependency $depTypeStr/$depName: must check integrity");
					$projectDepVersion = $projectDeps[$depTypeStr][$depName]["version"];
					if (!in_array($projectDepVersion, $depVersions))
					{
						return $this->quitError("Your project depends on $depTypeStr/$depName version $projectDepVersion while module $moduleName-$moduleVersion requires one of the following versions: ".join(", ", $depVersions));
					}
				}
				else
				{
					$this->debugMessage("New dependency $depTypeStr/$depName: nothing to check (install passed)");
					$depVersion = f_util_ArrayUtils::lastElement($depVersions);
					switch ($depTypeStr)
					{
						case "module":
							$modulesToInstall[] = array("path" => $bootStrap->getComponentPath($depType, $depName, $depVersion),
								"name" => $depName, "version" => $depVersion);
							break;
						case "lib":
						case "change-lib":
							$libsToInstall[] = array("path" => $bootStrap->getComponentPath($depType, $depName, $depVersion),
							"name" => $depName, "version" => $depVersion);
							break;
						case "lib-pear":
							$pearLibsToInstall[] = array("path" => $bootStrap->getComponentPath($depType, $depName, $depVersion),
							"name" => $depName, "version" => $depVersion);
							break;
					}
				}
			}
		}
		
		$modulesToInstall[] = array("name" => $moduleName, "path" => $modulePath, "version" => $moduleVersion);
		
		$this->okMessage("Dependencies OK");
		
		foreach ($libsToInstall as $libInfo)
		{
			$this->message("Symlink ".$libInfo["name"]."-".$libInfo["version"]);
			f_util_FileUtils::symlink($libInfo["path"], WEBEDIT_HOME."/libs/".$libInfo["name"]);
			$this->changecmd("update-autoload", array(WEBEDIT_HOME."/libs/".$libInfo["name"]));
		}
		
		if (count($pearLibsToInstall) > 0)
		{
			if (isset($projectDeps["PEAR_DIR"]) &&  isset($projectDeps["lib-pear"]))
			{
				$pearDir = $projectDeps["PEAR_DIR"];
				foreach ($pearLibsToInstall as $libInfo)
				{
					$libName = $libInfo["name"];
					
					$this->message("Symlink pearlibs/$libName-".$libInfo["version"]);
					if (f_util_FileUtils::symlink($libInfo["path"], WEBEDIT_HOME."/libs/pearlibs/".$libName, f_util_FileUtils::OVERRIDE))
					{
						if ($projectDeps['PEAR_WRITEABLE'])
						{
							$this->message("copy libs/pearlibs/".$libName . " to " . $pearDir);
							f_util_FileUtils::cp(WEBEDIT_HOME."/libs/pearlibs/".$libName, $pearDir, 
							f_util_FileUtils::OVERRIDE + f_util_FileUtils::APPEND, array('change.xml', 'tests', 'docs'));
						}
						else
						{
							$this->message("Please check if $libName-".$libInfo["version"] . " PEAR extension is correctly installed!");
						}
					}
				}
				$this->changecmd("update-autoload", array($pearDir));
			}
		}
		
		foreach ($modulesToInstall as $modInfo)
		{
			$this->message("Symlink ".$modInfo["name"]."-".$modInfo["version"]);
			f_util_FileUtils::symlink($modInfo["path"], WEBEDIT_HOME."/modules/".$modInfo["name"]);
			$this->changecmd("update-autoload", array(WEBEDIT_HOME."/modules/".$modInfo["name"]));
		}
		
		$this->changecmd("compile-all");
		$this->changecmd("generate-database");
		foreach ($modulesToInstall as $modInfo)
		{
			$this->changecmd("import-init-data", array($modInfo["name"]));
		}
        $this->changecmd("init-webapp");
		$doc = f_util_DOMUtils::getDocument(WEBEDIT_HOME."/change.xml");
		$doc->registerNamespace("c", "http://www.rbs.fr/schema/change-project/1.0");
		if (!$doc->exists("c:dependencies/c:modules/c:module[text() = '$moduleFullName']"))
		{
			$modulesElem = $doc->findUnique("c:dependencies/c:modules");
			$moduleElem = $doc->createElement("module", $moduleFullName);
			$modulesElem->appendChild($moduleElem);
			
			f_util_FileUtils::write(WEBEDIT_HOME."/change.xml", $doc->saveXML(), f_util_FileUtils::OVERRIDE);
			$this->okMessage("Project descriptor updated");
		}
		
		return $this->quitOk("Install OK");
	}
}
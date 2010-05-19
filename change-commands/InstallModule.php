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
	function getParameters($completeParamCount, $params, $options)
	{
		return c_ChangeBootStrap::getLastInstance()->getRemoteModules();
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
		
		if (!preg_match("/^.*-[0-9].*$/", $moduleFullName))
		{
			return $this->quitError("'$moduleFullName' is not a valid component name");
		}
		
		$bootStrap = $this->getParent()->getBootStrap();
		$index = strpos($moduleFullName, "-");
		$moduleName = substr($moduleFullName, 0, $index);
		$moduleVersion = substr($moduleFullName, $index+1);
		
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
		list ($declaredDeps, $computedDeps) = $bootStrap->getDependencies($modulePath);
		
		$modulesToInstall = array();
		$libsToInstall = array();
		
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
		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace("c", "http://www.rbs.fr/schema/change-project/1.0");
		if ($xpath->query("c:dependencies/c:modules/c:module[text() = '$moduleFullName']")->length == 0)
		{
			$modulesElem = $xpath->query("c:dependencies/c:modules")->item(0);
			$moduleElem = $doc->createElement("module", $moduleFullName);
			//$moduleElem = $doc->createElementNS("http://www.rbs.fr/schema/change-project/1.0", "c:module", $moduleFullName);
			$modulesElem->appendChild($moduleElem);
			
			f_util_FileUtils::write(WEBEDIT_HOME."/change.xml", $doc->saveXML(), f_util_FileUtils::OVERRIDE);
			$this->okMessage("Project descriptor updated");
		}
		
		$this->quitOk("Install OK");
	}
}
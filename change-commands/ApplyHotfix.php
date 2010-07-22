<?php
class commands_ApplyHotfix extends commands_CheckHotfix
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<hotfix>";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "apply an hot fix";
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 1
			&& (is_numeric($params[0]) || preg_match('/^.*-[0-9]+$/', $params[0])); 
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->loadFramework();
		$patches = PatchService::getInstance()->check();
		if (count($patches) > 0)
		{
			$errStr = "Your project must apply the following patches before to apply any hotfix:\n";
			foreach ($patches as $packageName => $patchList)
			{
				$module = str_replace('modules_', '', $packageName);
				foreach ($patchList as $patchName)
				{
					$errStr .= '  change.php apply-patch ' . $module . ' ' . $patchName."\n";
				}
			}
			return $this->quitError($errStr);
		}
		
		$hotfix = $params[0];
		if (is_numeric($hotfix))
		{
			$hotfixNumber = intval($hotfix);
		}
		else
		{
			$matches = null;
			if (!preg_match('/^(.*)-([0-9]+)$/', $hotfix, $matches))
			{
				return $this->quitError("Invalid hotfix $hotfix");
			}
			$hotfixNumber = intval($matches[2]);
		}
		
		$hotfixes = $this->getHotfixes();
		if (f_util_ArrayUtils::isEmpty($hotfixes) || !isset($hotfixes[$hotfixNumber]))
		{
			return $this->quitError("Hotfix $hotfixNumber is not available for your project");
		}
		$firstHotfix = f_util_ArrayUtils::firstElement($hotfixes);
		if ($firstHotfix["number"] != $hotfixNumber)
		{
			return $this->quitError("You must first apply hotfix number ".$firstHotfix["number"]." (".$firstHotfix["type"]."/".$firstHotfix["name"]."-".$firstHotfix["version"]."). See change.php check-hotfix");
		}
		
		$bootStrap = $this->getParent()->getBootStrap();
		$projectDeps = $this->getComputedDeps();
		$hotfixPath = $bootStrap->installComponent($firstHotfix["type"], $firstHotfix["name"], $firstHotfix["version"]."-".$firstHotfix["number"]);
		
		list ( , $hotfixComputedDeps) = $bootStrap->getDependencies($hotfixPath);
		$projectDeps = $this->getComputedDeps();
		
		$modulesToInstall = array();
		$libsToInstall = array();
		$pearLibsToInstall = array();
		
		foreach ($hotfixComputedDeps as $depType => $deps)
		{
			$depTypeStr = $bootStrap->getDepTypeAsString($depType);
			foreach ($deps as $depName => $depVersions)
			{
				$depVersion = f_util_ArrayUtils::lastElement($depVersions);
				if (!isset($projectDeps[$depTypeStr][$depName]))
				{
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
						case "pear-lib":
							$pearLibsToInstall[] = array("path" => $bootStrap->getComponentPath($depType, $depName, $depVersion),
							"name" => $depName, "version" => $depVersion);
							break;
					}
				}
				else
				{
					// check version ... ?
				}
			}
		}
		
		f_util_System::execChangeCommand("disable-site");
		
		// TODO refactor with install-module
		
		foreach ($libsToInstall as $libInfo)
		{
			$this->message("Symlink ".$libInfo["name"]."-".$libInfo["version"]);
			f_util_FileUtils::symlink($libInfo["path"], WEBEDIT_HOME."/libs/".$libInfo["name"]);
			f_util_System::execChangeCommand("update-autoload", array(WEBEDIT_HOME."/libs/".$libInfo["name"]));
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
				f_util_System::execChangeCommand("update-autoload", array($pearDir));
			}
		}
		
		if (count($modulesToInstall) > 0)
		{
			foreach ($modulesToInstall as $modInfo)
			{
				$this->message("Symlink ".$modInfo["name"]."-".$modInfo["version"]);
				f_util_FileUtils::symlink($modInfo["path"], WEBEDIT_HOME."/modules/".$modInfo["name"]);
				f_util_System::execChangeCommand("update-autoload", array(WEBEDIT_HOME."/modules/".$modInfo["name"]));
			} 
			
			f_util_System::execChangeCommand("compile-all");
			f_util_System::execChangeCommand("generate-database");
			foreach ($modulesToInstall as $modInfo)
			{
				f_util_System::execChangeCommand("import-init-data", array($modInfo["name"]));
			}
	        f_util_System::execChangeCommand("init-webapp");
		}
		
		$isFramework = false;
		$isModule = false;
		
		switch ($firstHotfix["type"])
		{
			case "change-lib":
				if ($firstHotfix["name"] == "framework")
				{
					f_util_FileUtils::symlink($hotfixPath, WEBEDIT_HOME."/framework", f_util_FileUtils::OVERRIDE);
					f_util_System::execChangeCommand("update-autoload", array(WEBEDIT_HOME."/framework"));
					$isFramework = true;
				}
				else
				{
					// Ignore: this should not be possible
				}
				break;
			case "module":
				f_util_FileUtils::symlink($hotfixPath, WEBEDIT_HOME."/modules/".$firstHotfix["name"], f_util_FileUtils::OVERRIDE);
				f_util_System::execChangeCommand("update-autoload", array(WEBEDIT_HOME."/modules/".$firstHotfix["name"]));
				$isModule = true;
				break;
			case "lib":
			case "lib-pear":
				// Ignore: this should not be possible
				break;
		}
		
		$patches = PatchService::resetInstance()->check();
		foreach ($patches as $packageName => $patchList)
		{
			$module = str_replace('modules_', '', $packageName);
			foreach ($patchList as $patchName)
			{
				f_util_System::execChangeCommand("apply-patch", array($module, $patchName));
			}
		}
		
		// TODO: update change.xml
		$descDom = f_util_DOMUtils::fromPath(WEBEDIT_HOME."/change.xml");
		$descDom->registerNamespace("c", "http://www.rbs.fr/schema/change-project/1.0");
		$depElem = null;
		if ($isFramework)
		{
			$depElem = $descDom->findUnique("c:dependencies/c:framework");
		}
		elseif ($isModule)
		{
			$depElem = $descDom->findUnique("c:dependencies/c:modules/c:module[text() = '".$firstHotfix["name"]."-".$firstHotfix["version"]."']");
			if ($depElem === null)
			{
				$depElem = $descDom->createElement("module");
				$depElem->appendChild($descDom->createTextNode($firstHotfix["name"]."-".$firstHotfix["version"]));
				$descDom->findUnique("c:dependencies/c:modules")->appendChild($depElem);
			}
		}
		
		if ($depElem !== null)
		{
			$hotfixAttr = array();
			if ($depElem->hasAttribute("hotfixes"))
			{
				$hotfixAttr[] = $depElem->getAttribute("hotfixes");
			}
			$hotfixAttr[] = $firstHotfix["number"];
			$depElem->setAttribute("hotfixes", join(",", $hotfixAttr));
			f_util_DOMUtils::save($descDom, WEBEDIT_HOME."/change.xml");
		}
		
		f_util_System::execChangeCommand("enable-site");
		
		return $this->quitOK("hotfix ".$hotfix." applied successfully");
	}
}
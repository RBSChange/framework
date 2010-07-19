<?php
class commands_CheckHotfix extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Checks for hotfix to apply";
	}
	
	/**
	 * @return array
	 */
	function getHotfixes()
	{
		$bootStrap = $this->getParent()->getBootStrap();
		$hotfixes = $bootStrap->getHotfixes(Framework::getVersion());
		//var_export($hotfixes);
		$computedDeps = $this->getComputedDeps();
		//var_export($computedDeps);
		$hotfixesFiltered = array();
		foreach ($hotfixes as $componentFullName => $hotfixVersions)
		{
			list($componentType, $componentName) = explode("/", $componentFullName);
			if (!isset($computedDeps[$componentType][$componentName]))
			{
				//echo "Ignore non installed ".$hotfix."\n";
				unset($hotfixes[$componentFullName]);
			}
			else
			{
				foreach ($hotfixVersions as $hotfixKey => $hotfix)
				{
					$installedVersion = $computedDeps[$componentType][$componentName]["version"];
					if ($bootStrap->compareVersion($installedVersion, $hotfix) >= 0)
					{
						//echo "Ignore already installed $componentFullName-$hotfix\n";
						unset($hotfixVersions[$hotfixKey]);
						if (count($hotfixVersions) == 0)
						{
							unset($hotfixes[$componentFullName]);
						}
					}
					else
					{
						//echo "Hotfix $componentFullName,$hotfix OK ($installedVersion)\n";
						$matches = null;
						if (!preg_match('/^(.*)-([0-9]+)$/', $hotfix, $matches))
						{
							throw new Exception("Bad hotfix version ".$hotfix);
						}
						$hotfixNumber = intval($matches[2]);
						$hotfixesFiltered[$hotfixNumber] = array("type" => $componentType, "name" => $componentName, "version" => $matches[1], "number" => $hotfixNumber);
					}
				}
			}
		}
		ksort($hotfixesFiltered);
		
		return $hotfixesFiltered;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$hotfixes = $this->getHotfixes();		
		if (f_util_ArrayUtils::isEmpty($hotfixes))
		{
			return $this->quitOk("No hotfix available for your project");
		}
		$this->message("You should apply the following hotfixes:");
		foreach ($hotfixes as $hotfixNumber => $componentInfo)
		{
			$this->message("- change.php apply-hotfix ".$componentInfo["type"]."/".$componentInfo["name"]."-".$componentInfo["version"]."-".$hotfixNumber);
		}
	}
}
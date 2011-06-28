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
		return count($params) == 1; 
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
					$errStr .= '  ' . CHANGE_COMMAND . ' apply-patch ' . $module . ' ' . $patchName."\n";
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
		if (!isset($hotfixes[$hotfixNumber]))
		{
			return $this->quitError("Hotfix $hotfixNumber is not available for your project");
		}
		
		$firstHotfixName = f_util_ArrayUtils::firstElement($hotfixes);
		list ($category, $componentName, $version, $hotfix) = explode('/', str_replace('-', '/', $firstHotfixName));
		if ($hotfix != $hotfixNumber)
		{
			return $this->quitError("You must first apply hotfix number $hotfix ($firstHotfixName). See check-hotfix");
		}
		
		$bootStrap = $this->getParent()->getBootStrap();
		$hotfixPath = $bootStrap->installComponent($category, $componentName, $version, $hotfix);
		if ($hotfixPath === null)
		{
			return $this->quitError("Unable to download hotfix $firstHotfixName");
		}
		
		$this->getParent()->executeCommand("disable-site");
		
		if (!$bootStrap->linkToProject($category, $componentName, $version, $hotfix))
		{
			return $this->quitError("Unable to link '$firstHotfixName' in project");
		}
		
		if (!$bootStrap->updateProjectDependencies($category, $componentName, $version, $hotfix))
		{
			return $this->quitError("Unable to update file project dependencies change.xml");
		}		
		
		$patches = PatchService::resetInstance()->check();
		foreach ($patches as $packageName => $patchList)
		{
			$module = str_replace('modules_', '', $packageName);
			foreach ($patchList as $patchName)
			{
				$this->getParent()->executeCommand("apply-patch", array($module, $patchName));
			}
		}

		$this->getParent()->executeCommand("enable-site");		
		return $this->quitOK("hotfix ".$hotfix." applied successfully");
	}
}
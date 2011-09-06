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
			$errStr = array("Your project must apply the following patches before to apply any hotfix:");
			foreach ($patches as $packageName => $patchList)
			{
				$module = str_replace('modules_', '', $packageName);
				foreach ($patchList as $patchName)
				{
					$errStr[]= $this->getChangeCmdName() . ' apply-patch ' . $module . ' ' . $patchName;
				}
			}
			return $this->quitError(implode(PHP_EOL, $errStr));
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
		
		$allHotFix = $this->getHotfixes();
		if (count($allHotFix) == 0)
		{
			return $this->quitError("No hotfix to apply. See check-hotfix");
		}
		
		
		/* @var $hotfixPackage c_Package */
		$hotfixPackage = f_util_ArrayUtils::firstElement($allHotFix);
		if ($hotfixPackage->getHotfix() != $hotfixNumber)
		{
			$hotFix = $hotfixPackage->getHotfix();
			$firstHotfixName = $hotfixPackage->__toString();
			return $this->quitError("You must first apply hotfix number $hotfix ($firstHotfixName). See check-hotfix");
		}
		
		$bootStrap = $this->getBootStrap();
		$tmpFile = null;
		$dr = $bootStrap->downloadFile($hotfixPackage->getDownloadURL(), $tmpFile);
		if ($dr !== true)
		{
			return $this->quitError($dr);
		}
		$tmpPath = $tmpFile . '.unzip';
		$tmpPackage = $bootStrap->unzipPackage($tmpFile, $tmpPath);
		if ($tmpPackage === null || 
			$tmpPackage->getKey() != $hotfixPackage->getKey() || 
			$tmpPackage->getHotfixedVersion() != $hotfixPackage->getHotfixedVersion())
		{
			$this->warnMessage('Invalid zip archive: ' . $tmpFile);
			return null;
		}
		
		$projPackages = $bootStrap->getProjectDependencies();
		
		$package = $projPackages[$tmpPackage->getKey()];
		
		/* @var $package c_Package */
		$package->setVersion($tmpPackage->getVersion());
		$package->setHotfix($tmpPackage->getHotfix());
		$bootStrap->updateProjectPackage($package);
		
		$this->executeCommand("disable-site");
		
		f_util_FileUtils::rmdir($tmpPackage->getPath());
		f_util_FileUtils::cp($tmpPackage->getTemporaryPath(), $tmpPackage->getPath());
		f_util_FileUtils::rmdir($tmpPackage->getTemporaryPath());
		
		$this->executeCommand("update-autoload" , array(substr($tmpPackage->getRelativePath(), 1)));
		
		$patches = PatchService::resetInstance()->check();
		foreach ($patches as $packageName => $patchList)
		{
			$module = str_replace('modules_', '', $packageName);
			foreach ($patchList as $patchName)
			{
				$this->executeCommand("apply-patch", array($module, $patchName));
			}
		}

		$this->getParent()->executeCommand("enable-site");	
			
		return $this->quitOK("hotfix ".$hotfix." applied successfully");
	}
}
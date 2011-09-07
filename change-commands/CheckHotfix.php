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
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$hotfixes = $this->getHotfixes();		
		if (count($hotfixes) == 0)
		{
			return $this->quitOk("No hotfix available for your project");
		}
		
		$this->message("You should apply the following hotfixes:");
		foreach ($hotfixes as $hotfixNumber => $package)
		{
			/* @var $package c_Package */
			$this->message($this->getChangeCmdName() . " apply-hotfix " . $package->__toString());
		}
	}
	
	/**
	 * @return c_Package[]
	 */
	function getHotfixes()
	{
		$hotfixesFiltered = array();
		$bootStrap = $this->getBootStrap();
		
		foreach ($bootStrap->getProjectDependencies() as $package) 
		{
			/* @var $package c_Package */
			if ($package->getDownloadURL() == null)
			{
				$relaseUrl = $package->getReleaseURL() ? $package->getReleaseURL() : $bootStrap->getReleaseRepository();
				$relPackages = $bootStrap->getReleasePackages($relaseUrl);
				if (is_array($relPackages) && isset($relPackages[$package->getKey()]))
				{
					$relPackage = $relPackages[$package->getKey()];
					/* @var $relPackage c_Package */
					$currentHf = $package->getHotfix() ? $package->getHotfix() : 0;
					foreach ($relPackage->getHotfixArray() as $hfNumber) 
					{
						if ($hfNumber > $currentHf)
						{
							$hfPackage = c_Package::getNewInstance($package->getType(), $package->getName(), PROJECT_HOME);
							$hfPackage->setVersion($relPackage->getVersion());
							$hfPackage->setHotfix($hfNumber);
							$hfPackage->setReleaseURL($relaseUrl);
							$hfPackage->populateDefaultDownloadUrl();
							$hotfixesFiltered[$hfNumber] = $hfPackage;
						}
					}
				}
				else
				{
					$this->warnMessage($package->getKey() . ' not found in Release ' . $relaseUrl);
				}
			}
		}
		ksort($hotfixesFiltered, SORT_NUMERIC);		
		return $hotfixesFiltered;
	}
}
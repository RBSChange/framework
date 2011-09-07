<?php
class commands_UpdateDependencies extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}
	
	function getAlias()
	{
		return "upddep";
	}
	
	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Update project dependencies";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Update project dependencies ==");
		$bootstrap = $this->getParent()->getBootStrap();
		$dependencies = $bootstrap->getProjectDependencies();
		
		$modulePaths =  glob(PROJECT_HOME . '/modules/*/install.xml');
		foreach ($modulePaths as $path) 
		{
			$name = basename(dirname($path));
			$doc = new DOMDocument('1.0', 'UTF-8');
			$doc->load($path);
			if ($doc->documentElement)
			{
				$p = c_Package::getInstanceFromPackageElement($doc->documentElement, PROJECT_HOME);
				if ($p->getType() === 'modules' && $p->getName() === $name && $p->getVersion())
				{
					if (!isset($dependencies[$p->getKey()]))
					{
						$this->log('Add module ' . $p->__toString() . ' in project install.xml');
						$bootstrap->updateProjectPackage($p);
						$dependencies[$p->getKey()] = $p;
					}
					continue;
				}
			}
			$this->warnMessage("Invalid Module signature in: " . $path);
		}
		$updateAutoload = false;
		$checked = array();
		while (true)
		{
			$newDeps = array();
			foreach ($dependencies as $package) 
			{
				/* @var $package c_Package */
				if (isset($checked[$package->getKey()])) {continue;}
				
				$checked[$package->getKey()] = true;
				if ($this->updateDependency($package)) {$updateAutoload = true;}
				
				$installDoc = $package->getInstallDocument();
				if ($installDoc)
				{
					foreach ($bootstrap->getDependenciesFromXML($installDoc) as $depPackage) 
					{
						if (!isset($dependencies[$depPackage->getKey()]))
						{
							$newDeps[$depPackage->getKey()] = $depPackage;
						}
					}
				}
			}
			if (count($newDeps) == 0) {break;}
			
			foreach ($newDeps as $depPackage) 
			{
				$dependencies[$depPackage->getKey()] = $depPackage;
			}
		}
		
		if ($updateAutoload)
		{
			$this->log('Update autoload...');
			$this->executeCommand('update-autoload');
		}
	}
	
	/**
	 * @param c_Package $package
	 * @return boolean Project files updated
	 */
	protected function updateDependency($package)
	{
		$bootstrap = $this->getParent()->getBootStrap();
		$currentXML = $package->getInstallDocument();
		$tmpPackage = $bootstrap->getPackageFromXML($currentXML);
		
		if ($tmpPackage === null) //Package not exist in project
		{
			if ($package->isStandalone()) //Nothing
			{
				$bootstrap->removeProjectDependency($package);
				$this->warnMessage("Remove standalone " . $package->getKey() . " from project install.xml");
				return false;
			}
			
			$downloadPackage = $this->downloadPackage($package, $package->getVersion() != null);
			if ($downloadPackage === null) //Package not Downloaded
			{
				$this->warnMessage("Invalid " . $package->getKey() . " in project install.xml");
				return false;
			}
			
			if ($package->getVersion() == null) //Update version from download
			{
				$package->setVersion($downloadPackage->getVersion());
				$package->setHotfix($downloadPackage->getHotfix());	
				$bootstrap->updateProjectPackage($package);
				$this->message("Update version of " . $package->getKey() . " in project install.xml");
			}
			
			$this->message('Copy ' . $package->getKey() . '-' . $package->getHotfixedVersion() . ' in project...');
			f_util_FileUtils::rmdir($package->getPath());
			f_util_FileUtils::cp($downloadPackage->getTemporaryPath(), $package->getPath());
			f_util_FileUtils::rmdir($downloadPackage->getTemporaryPath());
			
			return true;
		}
		
		if ($package->getHotfixedVersion() !== $tmpPackage->getHotfixedVersion())
		{		
			$package->setVersion($tmpPackage->getVersion());
			$package->setHotfix($tmpPackage->getHotfix());	
			$bootstrap->updateProjectPackage($package);
			$this->message("Update version of " . $package->getKey() . " in project install.xml");
		}
		
		return false;
	}
	
	/**
	 * @param c_Package $package
	 * @param boolean $usePackageVersion
	 * @return c_Package or null
	 */
	protected function downloadPackage($package, $usePackageVersion = false)
	{
		$bootstrap = $this->getParent()->getBootStrap();	
		$downloadURL = $package->getDownloadURL();
		if ($downloadURL === null)
		{
			$releaseURL = $package->getReleaseURL() == null ? $bootstrap->getReleaseRepository() : $package->getReleaseURL();
			$releasePackages = $bootstrap->getReleasePackages($releaseURL);
			if (!is_array($releasePackages))
			{
				$this->warnMessage('Inavlid releaseURL: ' . $releaseURL);
				return null;
			}
			if (!isset($releasePackages[$package->getKey()]))
			{
				$this->warnMessage('Inavlid package: ' . $package->getKey() . ' in ' . $releaseURL);
				return null;
			}
			
			if ($usePackageVersion)
			{
				$downloadURL = $releaseURL . $package->getRelativeReleasePath() . '.zip';
			}
			else
			{
				$downloadURL = $releasePackages[$package->getKey()]->getDownloadURL();
			}
		}
		
		$this->message('Download '. $downloadURL . '...');		
		$tmpFile = null;
		$dr = $bootstrap->downloadFile($downloadURL, $tmpFile);
		if ($dr !== true)
		{
			$this->warnMessage($dr);
			return null;
		}
		
		$tmpPath = $tmpFile . '.unzip';
		$tmpPackage = $bootstrap->unzipPackage($tmpFile, $tmpPath);
		if ($tmpPackage === null)
		{
			$this->warnMessage('Invalid zip archive: ' . $tmpFile);
			return null;
		}
		elseif ($tmpPackage->getKey() != $package->getKey())
		{
			$this->warnMessage('Invalid package : ' . $tmpPackage->getKey());
			f_util_FileUtils::rmdir($tmpPackage->getTemporaryPath());
			return null;
		}
		elseif ($usePackageVersion && $tmpPackage->getHotfixedVersion() != $package->getHotfixedVersion())
		{
			$this->warnMessage('Invalid package version: ' . $tmpPackage->getHotfixedVersion());
			f_util_FileUtils::rmdir($tmpPackage->getTemporaryPath());
			return null;
		}
		
		return $tmpPackage;		
	}
}
<?php
class commands_Install extends c_ChangescriptCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "modules|themes|libs name relaseURL|DownloadURL|ArchivePath [--post-install]";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "install a module, theme or lib";
	}
	
	function getOptions()
	{
		return array('recursive', 'post-install');
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		if (count($params) < 2)
		{
			return false;
		}
		if (!in_array($params[0], array('modules', 'themes', 'libs')))
		{
			return false;
		}
		if (!preg_match('/^[a-z0-9]+$/', $params[1]))
		{
			$this->errorMessage('Invalid composant name: ' . $params[1]);
			return false;
		}
		$p = c_Package::getNewInstance($params[0], $params[1], PROJECT_HOME);
		if (isset($options['post-install']))
		{
			if (!$p->isInProject())
			{
				$this->errorMessage($p->getKey() . ' Not in project.');
				return false;
			}			
		}
		elseif ($p->isInProject())
		{
			$this->errorMessage($p->getKey() . ' Already in project.');
			return false;
		}
		return true;
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 0)
		{
			return array('modules', 'themes', 'libs');
		}
		if ($completeParamCount == 1)
		{
			$names = array();
			$packages = $this->getBootStrap()->getReleasePackages($this->getBootStrap()->getReleaseRepository());
			foreach ($packages as $package) 
			{
				/* @var $package c_Package */
				if ($package->getType() === $params[0] && !$package->isInProject())
				{
					$names[] = $package->getName();
				}
			}
			return (count($names)) ? $names : null;
		}
		return null;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$recursive = isset($options['$options']);
		
		$filesToClean = array();
		$bootstrap = $this->getBootStrap();
		$type = $params[0];
		$name = $params[1];
		$p = c_Package::getNewInstance($type, $name, PROJECT_HOME);
		
		$this->message("== Install " . $p->getKey(). " ==");	
		if (!isset($options['post-install']))
		{
			$src = isset($params[2]) ? $params[2] : null;
			$downloadURL = null;
			$zipPath = null;
			if ($src !== null)
			{
				if (substr($src, -4) === '.zip')
				{
					if (substr($src, 0, 7) === 'http://')
					{
						$downloadURL = $src;
					}
					else
					{
						$zipPath = $src;
					}
				}
				elseif (substr($src, 0, 7) === 'http://')
				{
					$packages = $bootstrap->getReleasePackages($src);
					if (is_array($packages) && isset($packages[$p->getKey()]))
					{
						$p->setReleaseURL($src);
						$downloadURL = $packages[$p->getKey()]->getDownloadURL();
					}
				}
			}
			else
			{
				$packages = $bootstrap->getReleasePackages($this->getBootStrap()->getReleaseRepository());
				if (is_array($packages) && isset($packages[$p->getKey()]))
				{
					$downloadURL = $packages[$p->getKey()]->getDownloadURL();
				}
			}
			
			if ($downloadURL !== null)
			{
				$dr = $bootstrap->downloadFile($downloadURL, $zipPath);
				if ($dr !== true)
				{
					return $this->quitError($dr);
				}
				$filesToClean[] = $zipPath;
				if ($p->getReleaseURL() == null)
				{
					$p->setDownloadURL($downloadURL);
				}
			}
			else
			{
				$p->setDownloadURL('none');
			}
			
			if (!is_readable($zipPath))
			{
				$this->cleanFiles($filesToClean);
				return $this->quitError('File: ' . $zipPath . ' is not readable');
			}
			
			$tempName = tempnam($bootstrap->getTmpPath(), 'zip');
			$filesToClean[] = $tempName;
			$tmpPath = $tempName . '.unzip';
			$filesToClean[] = $tmpPath;
			
			$tmpPackage = $bootstrap->unzipPackage($zipPath, $tmpPath);
			if ($tmpPackage === null)
			{
				$this->cleanFiles($filesToClean);
				return $this->quitError('Unable to decompress: ' . $zipPath . ' or is not a valid Package');			
			}
			else if ($tmpPackage->getKey() != $p->getKey())
			{
				$this->cleanFiles($filesToClean);
				return $this->quitError('Invalid Package Signature: ' . $tmpPackage->getKey());			
			}
			
			$this->message('Copy ' . $tmpPackage->getTemporaryPath() . ' in ' . $p->getPath() . '...');
			f_util_FileUtils::cp($tmpPackage->getTemporaryPath(), $p->getPath());
			
			$this->cleanFiles($filesToClean);
		}
		$bootstrap->updateProjectPackage($p);
				
		switch ($type) 
		{
			case 'modules':
				$this->postInstallModules($p, $recursive);		
				break;
			case 'themes':
				$this->postInstallThemes($p);
				break;
			case 'libs':
				$this->postInstallLibs($p);
			break;
		}
		
		return $this->quitOk("Install $type OK");
	}
	
	/**
	 * @param c_Package $p
	 * @param boolean $recursive
	 */
	protected function postInstallModules($p, $recursive)
	{
		$this->executeCommand("update-autoload" , array('modules/' . $p->getName()));
		$this->executeCommand("init-patch-db", array($p->getName()));
		
		$xml = $p->getInstallDocument();
		$projDeps = $this->getBootStrap()->getProjectDependencies();
		$packages = $this->getBootStrap()->getDependenciesFromXML($xml);
		foreach ($packages as $dep) 
		{
			/* @var $dep c_Package */
			if (!isset($projDeps[$dep->getKey()]))
			{
				if ($dep->isStandalone())
				{
					$this->warnMessage('Unable to install ' . $dep->getKey() . ' standalone dependency.');
					continue;
				}
				
				$params = array($dep->getType(), $dep->getName());
				if ($dep->getDownloadURL()) 
				{
					$params[] = $dep->getDownloadURL();
				}
				elseif ($dep->getReleaseURL()) 
				{
					$params[] = $dep->getReleaseURL();
				}
				$params[] = '--recursive';
				$this->executeCommand('install', $params);
			}
		}
					
		if ($recursive) {return;}
		
		$this->executeCommand("compile-all");
		$this->executeCommand("generate-database");
		$this->executeCommand("import-init-data");	//TODO: Update for module only ?			
        $this->executeCommand("init-webapp");		
	}

	/**
	 * @param c_Package $p
	 */
	protected function postInstallThemes($p)
	{
		$this->executeCommand('theme.install', array($p->getName()));
	}
	
	/**
	 * @param c_Package $p
	 */
	protected function postInstallLibs($p)
	{
		ClassResolver::getInstance()->appendDir(PROJECT_HOME . '/libs');
	}
	
	protected function cleanFiles($filesToClean)
	{
		foreach ($filesToClean as $path) 
		{
			if (is_dir($path))
			{
				f_util_FileUtils::rmdir($path);
			}
			else
			{
				@unlink($path);
			}
		}
	}
}
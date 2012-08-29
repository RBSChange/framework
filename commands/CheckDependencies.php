<?php
class commands_CheckDependencies extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "[--verbose] [--xml]";
	}
	
	function getAlias()
	{
		return "checkdep";
	}
	
	/**
	 * @return string[]
	 */
	function getOptions()
	{
		return array('--verbose', '--xml');
	}
	
	/**
	 * @return string
	 */
	function getDescription()
	{
		return "Checks project dependencies";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		if (isset ($options['xml']))
		{
			echo $this->executeXml($params);
			return;
		}
		$this->message("== Check project dependencies ==");
		$bootstrap = $this->getBootStrap();
		$cpDeps = $bootstrap->getComputedDependencies();
		$baseURL = $bootstrap->getReleaseRepository();		

		$this->okMessage('Release ' . $bootstrap->getRelease() . ' From: ' . $baseURL);
		$ap = $bootstrap->getArchivePath();
		if ($ap)
		{
			$this->okMessage('Archive Release path: ' . $ap);
		}
		$this->okMessage('Temporary path: ' . $bootstrap->getTmpPath());
		$this->okMessage("External depencendies:");
		if ($cpDeps['INCLUDE_PATH'])
		{
			$this->okMessage(" - Include Path: " . $cpDeps['INCLUDE_PATH']);
		}
		$this->okMessage(" - Zend Include Path: " . $cpDeps['ZEND_FRAMEWORK_PATH']);
		
		$changeXmlPath = $bootstrap->getDescriptorPath();
		$this->okMessage('Project depencendies: ' .$changeXmlPath);
		
		$dependencies = $cpDeps['dependencies'];
		
		$postDataArray = array();
		foreach ($dependencies as $package) 
		{
			/* @var $package c_Package */
			if ($package->isModule())
			{
				$data = array('repo' => !$package->isStandalone() , 'version' => $package->getVersion());
				$postDataArray[$package->getName()] = $data;
			}
		}		

				
		$filePath = null;
		$licenseStatus = 'Unknown (Remote repository not responding)';
		$errorModules = array();

		/* @var $baseURL string */
		$result = $bootstrap->downloadRepositoryFile($baseURL . '/license.xml', $filePath, array('modules' => $postDataArray));
		if ($result === true)
		{
			$xml = new DOMDocument('1.0', 'UTF-8');
			if ($xml->load($filePath) === true)
			{
				$licenseStatus = $xml->documentElement->hasAttribute('status') ? $xml->documentElement->getAttribute('status') : 'Unknown';
				$mnl = $xml->getElementsByTagName('modules');
				if ($mnl->length)
				{
					foreach ($mnl->item(0)->getElementsByTagName('module') as $mn)
					{
						/* @var $mn DOMElement */
						$errorModules[$mn->getAttribute('name')] = $mn->getAttribute('status');
					}
				}
				unlink($filePath);
			}
			else
			{
				$licenseStatus = 'Invalid License Informations';
				unlink($filePath);
			}
		}

		$this->okMessage("License: " . $bootstrap->getProperties()->getProperty("PROJECT_LICENSE") . ' : ' . $licenseStatus);
		
		foreach ($dependencies as $package) 
		{
			/* @var $package c_Package */
			$msg =  'Dependency: ' . $package->getKey() . ' version ' . $package->getVersion();
			if 	(!$package->isInProject())
			{
					$msg .= ': Not present in project';
					$this->warnMessage($msg);
					continue;
			}
			else
			{
				$installXMLDoc = $package->getInstallDocument();
				if ($installXMLDoc === null)
				{
					$msg .= ': install.xml not found';
					$this->warnMessage($msg);
					continue;
				}
				$reallyPackage = $bootstrap->getPackageFromXML($installXMLDoc);
				if ($reallyPackage->getKey() != $package->getKey())
				{
					$msg .= ': invalid install package ' . $reallyPackage->getKey();
					$this->warnMessage($msg);
					continue;
				}
				elseif ($reallyPackage->getVersion() != $package->getVersion())
				{
					$msg .= ': invalid install version ' . $reallyPackage->getVersion();
					$this->warnMessage($msg);
					continue;
				}
				elseif ($package->isModule() && isset($errorModules[$package->getName()]))
				{
					$msg .= ': ' . $errorModules[$package->getName()];
					$this->warnMessage($msg);
				}
				else
				{
					$error = false;
					foreach ($bootstrap->getDependenciesFromXML($installXMLDoc) as $package) 
					{
						if (!isset($dependencies[$package->getKey()]))
						{
							$error = true;
							$this->warnMessage($msg . ' Implicite dependency not found ' . $package->getKey());
						}
					}
					if ($error) {continue;}
				}
			}
			
			if (isset($options['verbose']))
			{
				$msg .= ': Ok';
				$this->okMessage($msg);
			}
		}
		
		return $this->quitOk('Project Checked successfully.');
	}

	function executeXml($params)
	{
		$domDoc = new DOMDocument("1.0", "UTF-8");
		$domDoc->formatOutput = true;
		$domDoc->preserveWhiteSpace = false;
		$domDoc->loadXml('<?xml version="1.0" encoding="UTF-8"?><dependencies></dependencies>');

		$cpDeps = $this->getComputedDeps();
		$dependencies = $cpDeps['dependencies'];
		foreach ($dependencies as $package) 
		{
			/* @var $package c_Package */
			if ($package->isFramework()) {continue;}
			
			$depNode = $domDoc->documentElement->appendChild($domDoc->createElement('dependency'));
			$depNode->setAttribute('type', $package->getType());
			$depNode->setAttribute('name', $package->getName());
			$depNode->setAttribute('version', $package->getVersion());
		}

		return $domDoc->saveXML();
	}
}
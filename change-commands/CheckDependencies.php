<?php
class commands_CheckDependencies extends commands_AbstractChangeCommand
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
		return "checkdep";
	}
	
	/**
	 * @return String[]
	 */
	function getOptions()
	{
		return array('verbose', 'xml');
	}
	
	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Checks project dependencies";
	}

	/**
	 * @param String[] $params
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
		$this->okMessage('Release Repo (' . $bootstrap->getRelease() . ') :' . $bootstrap->getReleaseRepository());
		
		$pearInfo = $bootstrap->loadPearInfo();
		$this->okMessage("External depencendies:");
		$this->okMessage(" - Pear Include Path: " . $cpDeps['PEAR_DIR']);
		$this->okMessage(" - Zend Include Path: " . $cpDeps['ZEND_FRAMEWORK_PATH']);
		
		$changeXmlPath = $bootstrap->getDescriptorPath();
		$this->okMessage('Project depencendies: ' .$changeXmlPath);
		
		$dependencies = $cpDeps['dependencies'];
		foreach ($dependencies as $package) 
		{
			/* @var $package c_Package */
			$msg =  'Dependency: ' . $package->getKey() . ' version ' . $package->getHotfixedVersion();
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
				elseif ($reallyPackage->getHotfixedVersion() != $package->getHotfixedVersion())
				{
					$msg .= ': invalid install version ' . $reallyPackage->getHotfixedVersion();
					$this->warnMessage($msg);
					continue;
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
			if ($package->getHotfix())
			{
				$depNode->setAttribute('hotfix', $package->getHotfix());
			}
		}

		return $domDoc->saveXML();
	}
}
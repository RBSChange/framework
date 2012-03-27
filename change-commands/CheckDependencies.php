<?php
class commands_CheckDependencies extends commands_AbstractChangeCommand
{
	/**
	 * @return String
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
		$bootstrap = $this->getParent()->getBootStrap();
		
		$changeXmlPath = $bootstrap->getDescriptorPath();
		foreach ($bootstrap->getRemoteRepositories() as $path) 
		{
			$this->okMessage('Remote Repo: ' . $path);
		}
		
		$this->okMessage('Project depencendies :' .$changeXmlPath);
		$hasWritable = false;
		foreach ($bootstrap->getLocalRepositories() as $path => $writable) 
		{
			$hasWritable = $hasWritable | $writable;
			$this->okMessage('Local Repo: ' . $path . ($writable ? ' writable' : 'read only'));
		}
		if (!$hasWritable)
		{
			$this->warnMessage('Project has no Writable repository');
		}

		$pearInfo = $bootstrap->loadPearInfo();
		$this->okMessage("Pear Informations:");
		$this->okMessage(" - Include Path: " . $pearInfo['include_path']);
		$this->okMessage(" - Writable: " . $pearInfo['writeable'] ? 'yes' : 'non');
		foreach ($pearInfo as $key => $data) 
		{
			if (isset($options['verbose']))
			$this->okMessage("Pear $key: $data");
		}
		$dependencies = $bootstrap->loadDependencies();
		
		foreach ($dependencies as $debType => $debs) 
		{
			foreach ($debs as $debName => $infos)
			{
				$msg =  (isset($infos['depfor'])) ?  'Implicit dependency ' : 'Dependency ';
				$msg .= $debType . '/' . $debName . ' version ' . $infos['version'];
				
				if (!$infos['localy'])
				{
					$msg .= ': Not localy';
					$this->warnMessage($msg);
				}
				elseif (!$infos['linked'])
				{
					$msg .= ': Not linked in project';
					$this->warnMessage($msg);
				}
				else if (isset($options['verbose']))
				{
					$msg .= ': Ok';
					$this->okMessage($msg);
				}
			} 
		}
		
		return $this->quitOk('Project Checked successfully.');
	}

	function executeXml($params)
	{
		$domDoc = new DOMDocument("1.0", "UTF-8");
		$domDoc->loadXml('<dependencies></dependencies>');
		// <dependency type="module" name="website" version="3.5.0" />
		$bootstrap = $this->getParent()->getBootStrap();

		
		$dependencies = $bootstrap->loadDependencies();

		foreach ($dependencies as $debType => $debs)
		{
			foreach ($debs as $debName => $infos)
			{
				$depNode = $domDoc->documentElement->appendChild($domDoc->createElement('dependency'));
				$depNode->setAttribute('type', $debType);
				$depNode->setAttribute('name', $debName);
				$depNode->setAttribute('version', $infos['version']);
			}
		}

		return $domDoc->saveXML();
	}
}
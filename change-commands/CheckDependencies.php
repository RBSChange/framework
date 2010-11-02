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
		return array('verbose');
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
		$this->message("== Check project dependencies ==");
		$bootstrap = $this->getParent()->getBootStrap();
		
		$changeXmlPath = $bootstrap->getDescriptorPath();
		foreach ($bootstrap->getRemoteRepositories() as $path) 
		{
			$this->okMessage('Remote Repo: ' . $path);
		}
		
		$this->okMessage('Project depencendies :' .$changeXmlPath);
		
		foreach ($bootstrap->getLocalRepositories() as $path => $writable) 
		{
			$this->okMessage('Local Repo: ' . $path . ($writable ? ' w' : 'r'));
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
				if (count($infos['hotfix']))
				{
					$msg .= '-' . max($infos['hotfix']);
				}
				
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
}
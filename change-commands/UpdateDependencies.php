<?php
class commands_UpdateDependencies extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "[--forcedownload]";
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

	function getOptions()
	{
		return array('forcedownload');
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
		$forceDownload = isset($options['forcedownload']);
		do 
		{
			$dependencies = $bootstrap->loadDependencies();
			$downloads = $this->getDepsToDownload($dependencies, $forceDownload);	
			foreach ($downloads as $depsInfos) 
			{
				list($debType, $componentName, $version) = $depsInfos;
				$fullName = $bootstrap->convertToCategory($debType) . '/' . $componentName .'-' . $version;
				
				$this->message('Download ' . $fullName . ' ...');
				try
				{
					$url = null;
					$path = $bootstrap->downloadDependency($debType, $componentName, $version, $url);
				} 
				catch (Exception $e) 
				{
					if ($forceDownload && $dependencies[$debType][$componentName]['localy'])
					{
						$this->warnMessage('Unable to Download : ' . $fullName . ' in local repository. ' . $e->getMessage());
					}
					else
					{
						return $this->quitError('Unable to Download : ' . $fullName . ' in local repository. ' . $e->getMessage());
					}
				}
			}
			$forceDownload = false;
		} 
		while (count($downloads) > 0);
		
		$moduleType = $bootstrap->convertToCategory(c_ChangeBootStrap::$DEP_MODULE);		
		$dependencies = $bootstrap->loadDependencies();
		$linkeds = $this->getDepsToLink($dependencies);
		foreach ($linkeds as $depsInfos) 
		{
			list($debType, $componentName, $version) = $depsInfos;
			
			$fullName = $bootstrap->convertToCategory($debType) . '/' . $componentName .'-' . $version;
			
			$this->message('linking ' . $fullName . ' ...');
			
			if (!$bootstrap->linkToProject($debType, $componentName, $version))
			{
				return $this->quitError('Unable to link : ' . $fullName . ' in project.');
			}
			
			if ($bootstrap->convertToCategory($debType) == $moduleType)
			{
				$moduleName = $componentName;
				if (is_dir("modules/$moduleName/change-commands"))
				{
					$this->getParent()->addCommandDir("modules/$moduleName/change-commands", "$moduleName|Module $moduleName commands");
				}
				if (is_dir("modules/$moduleName/changedev-commands"))
				{
					$this->getParent()->addGhostCommandDir("modules/$moduleName/changedev-commands", "$moduleName|Module $moduleName commands");
				}
			}
		}
	
		$bootstrap->cleanDependenciesCache();
		return $this->quitOk('Update Checked successfully.');
	}
	
	private function getDepsToDownload($dependencies, $forceDownload)
	{
		$result = array();
		foreach ($dependencies as $debType => $debs) 
		{
			foreach ($debs as $debName => $infos)
			{
				if ($forceDownload || !$infos['localy'])
				{
					$result[] = array($debType, $debName, $infos['version']) ;
				}
			} 
		}	
		return $result;	
	}
	
	private function getDepsToLink($dependencies)
	{
		$result = array();
		foreach ($dependencies as $debType => $debs) 
		{
			foreach ($debs as $debName => $infos)
			{
				if (!$infos['linked'])
				{
					$result[] = array($debType, $debName, $infos['version']) ;
				}
			} 
		}	
		return $result;	
	}	
}
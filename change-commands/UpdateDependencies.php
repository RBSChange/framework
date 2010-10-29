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
		
		do 
		{
			$dependencies = $bootstrap->loadDependencies();
			$downloads = $this->getDepsToDownload($dependencies);
			foreach ($downloads as $repositoryPath) 
			{
				list($debType, $componentName, $version, $hotfix) = $bootstrap->explodeRepositoryPath($repositoryPath);
				$this->message("Download $componentName-$version ($hotfix)...");
				$result = $bootstrap->installComponent($debType, $componentName, $version, $hotfix);
				if ($result === null)
				{
					return $this->quitError('Unable to download : ' . $repositoryPath . ' in local repository.');
				}
			}
		} 
		while (count($downloads) > 0);
		

		$dependencies = $bootstrap->loadDependencies();
		$linkeds = $this->getDepsToLink($dependencies);
		foreach ($linkeds as $repositoryPath) 
		{
			list($debType, $componentName, $version, $hotfix) = $bootstrap->explodeRepositoryPath($repositoryPath);
			$this->message("linking $componentName-$version ($hotfix)...");
			if (!$bootstrap->linkToProject($debType, $componentName, $version, $hotfix))
			{
				return $this->quitError('Unable to link : ' . $repositoryPath . ' in project.');
			}
			
			if ($debType == c_ChangeBootStrap::$DEP_MODULE)
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
	
	private function getDepsToDownload($dependencies)
	{
		$result = array();
		foreach ($dependencies as $debType => $debs) 
		{
			foreach ($debs as $debName => $infos)
			{
				if (!$infos['localy'])
				{
					$result[] = $infos['repoRelativePath'];
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
					$result[] = $infos['repoRelativePath'];
				}
			} 
		}	
		return $result;	
	}	
}
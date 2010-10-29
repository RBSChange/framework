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
	
	function getInstalledRepositoryPaths()
	{
		$result = array();
		$bootStrap = $this->getParent()->getBootStrap();
		$computedDeps = $bootStrap->getComputedDependencies();	
		foreach ($computedDeps as $category => $components) 
		{
			if (!is_array($components)) {continue;}
			foreach ($components as $componentName => $infos) 
			{
				if ($infos['linked'])
				{
					list($depType, $componentName, $version, $hotFix) = $bootStrap->explodeRepositoryPath($infos['repoRelativePath']);
					$result[$depType .'/'. $componentName .'/'. $version] = $hotFix ? $hotFix : 0;
				}
			}
		}
		return $result;
	}
	
	/**
	 * @return array
	 * @example   3 => '/framework/framework-3.0.3-3',
	 * 		 	  12 => '/framework/framework-3.0.3-12',
	 */
	function getHotfixes()
	{
		$bootStrap = $this->getParent()->getBootStrap();
		$hotfixes = $bootStrap->getHotfixes(Framework::getVersion());
		$computedDeps = $this->getInstalledRepositoryPaths();
		
		$hotfixesFiltered = array();
		foreach ($hotfixes as  $hotfixPath)
		{
			list($hf_depType, $hf_componentName, $hf_version, $hf_hotFix) = $bootStrap->explodeRepositoryPath($hotfixPath);
			$hfKey = $hf_depType .'/'. $hf_componentName .'/'. $hf_version;
			if (isset($computedDeps[$hfKey]) && $hf_hotFix > $computedDeps[$hfKey])
			{
				$hotFixName = $bootStrap->convertToCategory($hf_depType) . '/' . $hf_componentName . '-' . $hf_version . '-' . $hf_hotFix; 
				$hotfixesFiltered[$hf_hotFix] = $hotFixName;
			}
		}
		ksort($hotfixesFiltered, SORT_NUMERIC);		
		return $hotfixesFiltered;
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
		foreach ($hotfixes as $hotfixNumber => $hotfixName)
		{
			$this->message(" apply-hotfix ".$hotfixName);
		}
	}
}
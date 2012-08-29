<?php
class commands_CheckPatch extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "";
	}
	
	function getAlias()
	{
		return "checkp";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "Checks for patch to apply";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Check for patch to apply ==");
		
		$this->loadFramework();
		$ps = PatchService::getInstance();
		
		$list =  $ps->check();
		if (count($list) > 0)
		{
			$patches = array();
			$this->log('New available patch list :');
			foreach ($list as $module => $patchList)
			{
				foreach ($patchList as $patchName)
				{
					$className = $ps->getPHPClassPatch($module, $patchName);
					if ($className)
					{
						$patch = new $className($this);
						$patches[] = $patch;
					}
				}
			}
			
			usort($patches, array($ps, 'sortPatchForExecution'));
				
			foreach ($patches as $patch) 
			{
				/* @var $patch change_Patch */
				$this->log($this->getChangeCmdName() . ' apply-patch ' . $patch->getModuleName() . ' ' . $patch->getNumber());
			}
		}
		else
		{
			$this->quitOk('Your project is up to date.');
		}
	}
}
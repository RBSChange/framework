<?php
class commands_CheckPatch extends commands_AbstractChangeCommand
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
		return "checkp";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Checks for patch to apply";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Check for patch to apply ==");
		
		$this->loadFramework();

		$list =  PatchService::getInstance()->check();
		if (count($list) > 0)
		{
			$this->log('New available patch list :');
			foreach ($list as $packageName => $patchList)
			{
				$module = str_replace('modules_', '', $packageName);
				foreach ($patchList as $patchName)
				{
					$this->log($this->getChangeCmdName() . ' apply-patch ' . $module . ' ' . $patchName);
				}
			}
		}
		else
		{
			$this->quitOk('Your project is up to date.');
		}
	}
}
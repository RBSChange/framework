<?php
class commands_ApplyPatch extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "[--all] [--ignorecommands] <moduleName|framework> <patchNumber> ";
	}

	function getAlias()
	{
		return "ap";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Applies a patch";
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 2 || isset($options['all']);
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount > 1)
		{
			return null;
		}
		
		$list = PatchService::getInstance()->check();
		if (f_util_ArrayUtils::isEmpty($list))
		{
			return null;
		}
		
		if ($completeParamCount == 0)
		{
			$components = array_keys($list);
			return $components;
		}
		elseif (isset($list[$params[0]]))
		{
			return $list[$params[0]];
		}
		return null;
	}



	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	function _execute($params, $options)
	{
		if (isset($options['all']))
		{
			return $this->_executeAll($params, $options);
		}
		$moduleName = $params[0];
		$patchNumber = $params[1];

		$this->message("== Apply patch $moduleName/$patchNumber ==");
		$this->loadFramework();
		
		
		$postCommands = array();

		try
		{
			$lists = PatchService::getInstance()->getPatchList($moduleName);
			if (!in_array($patchNumber, $lists))
			{
				return $this->quitError('The patch '.$moduleName.'/'.$patchNumber.' cannot be found.');
			}
			
			// Get a instance of class
			$className = PatchService::getInstance()->getPHPClassPatch($moduleName, $patchNumber);
			if ($className === null)
			{
				return $this->quitError('The patch '.$moduleName.'/'.$patchNumber.' cannot be loaded.');
			}
			
			$patchInstance = new $className($this);
			if (!($patchInstance instanceof change_Patch)) 
			{
				return $this->quitError('The patch '.$moduleName.'/'.$patchNumber.' is not à valid patch.');
			}
			
			if (!isset($options['ignorecommands']))
			{
				$commands = $patchInstance->getPreCommandList();
				if (is_array($commands))
				{
					foreach ($commands as $commandInfo) 
					{
						if (is_array($commandInfo))
						{
							$cmdName = array_shift($commandInfo);
							$this->executeCommand($cmdName, $commandInfo);
						}
					}
				}
			}
			
			$patchInstance->executePatch();
			PatchService::getInstance()->patchApply($patchInstance);

			if (!isset($options['ignorecommands']))
			{
				$commands = $patchInstance->getPostCommandList();
				if (is_array($commands))
				{
					foreach ($commands as $commandInfo) 
					{
						if (is_array($commandInfo))
						{
							$cmdName = array_shift($commandInfo);
							$this->executeCommand($cmdName, $commandInfo);
						}
					}
				}
			}			
		}
		catch (Exception $e)
		{
			return $this->quitError("Application of patch \"".$moduleName."/".$patchNumber."\" failed:\n".$e->getMessage()."\n".$e->getTraceAsString());
		}
		
		return $this->quitOk('Patch "' . $moduleName.'/'.$patchNumber . '" successfully applied.');
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	function _executeAll($params, $options)
	{
		$this->message("== Apply all patch ==");
		$this->loadFramework();
		
		$ps = PatchService::getInstance();
		$list =  $ps->check();
		if (count($list) > 0)
		{
			$patches = array();
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
			
			
			$preCommands = array();
			$postCommands = array();
			
			if (!isset($options['ignorecommands']))
			{
				foreach ($patches as $patch) 
				{
					/* @var $patch change_Patch */
					$commands = $patch->getPreCommandList();
					if (is_array($commands))
					{
						foreach ($commands as $commandInfo) 
						{
							if (is_array($commandInfo) && count($commandInfo) >= 1)
							{
								$key = md5(implode(' ', $commandInfo));
								if (isset($preCommands[$key])) {unset($preCommands[$key]);}
								$preCommands[$key] = $commandInfo;
							}
						}
					}
					
					$commands = $patch->getPostCommandList();
					if (is_array($commands))
					{
						foreach ($commands as $commandInfo) 
						{
							if (is_array($commandInfo) && count($commandInfo) >= 1)
							{
								$key = md5(implode(' ', $commandInfo));
								if (isset($postCommands[$key])) {unset($postCommands[$key]);}
								$postCommands[$key] = $commandInfo;
							}
						}
					}
				}
			}
			
			foreach ($preCommands as $commandInfo) 
			{
				$cmdName = array_shift($commandInfo);
				$this->executeCommand($cmdName, $commandInfo);
			}
			
			foreach ($patches as $patch) 
			{
				$this->executeCommand('apply-patch', array($patch->getModuleName(), $patch->getNumber(), '--ignorecommands'));
			}
			
		
			foreach ($postCommands as $commandInfo) 
			{
				$cmdName = array_shift($commandInfo);
				$this->executeCommand($cmdName, $commandInfo);
			}
		}
		else
		{
			return $this->quitOk('Your project is up to date.');
		}
	}
}
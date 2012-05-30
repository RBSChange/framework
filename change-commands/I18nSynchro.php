<?php
class commands_I18nSynchro extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "[--reset|--init]";
	}

	/**
	 * @return NULL
	 */
	public function getOptions()
	{
		return array('reset', 'init');
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "working with I18n synchronization";
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		return null;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== I18n synchronization ==");
		$this->loadFramework();

		
		if (!RequestContext::getInstance()->hasI18nSynchro())
		{
			return $this->quitOk("No I18n synchronization configured");
		}
		$ls = LocaleService::getInstance();
		$scriptPath = 'framework/bin/batchI18nSynchro.php';
		
		if (isset($options['reset']) || isset($options['init']))
		{
			$cmd = isset($options['init']) ?  'init' : 'reset';
			$modelNamesByModules  = f_persistentdocument_PersistentDocumentModel::getDocumentModelNamesByModules();
			foreach ($modelNamesByModules as $module => $modelNames)
			{
				foreach ($modelNames as $modelName)
				{
					$lastIndexId = 0;	
					while (true)
					{		
						$output = f_util_System::execScript($scriptPath, array($cmd, $modelName, $lastIndexId));
						if (is_numeric($output))
						{
							$lastIndexId = intval($output);
							$this->log($modelName . ': ' . $lastIndexId);
						}
						else
						{
							if (!f_util_StringUtils::endsWith($output, 'OK'))
							{
								$this->warnMessage('Error on model:' . $modelName . ', lastId: ' . $lastIndexId . ', ' . $output);
							}
							break;
						}
					}
				}
			}
			
			return $this->quitOk($cmd . " I18n synchronization successfully");
		}
		
		$clear = false;
		while (true)
		{
			$output = f_util_System::execScript($scriptPath, array('synchro'));
			if (is_numeric($output))
			{
				$clear = true;
				$this->log('Processing: ' . $output . ' documents');
			}
			else
			{
				if (!f_util_StringUtils::endsWith($output, 'OK'))
				{
					$this->warnMessage('Error:' . $output);
				}
				break;
			}
		}
		
		if ($clear)
		{
			$parent = $this->getParent();
			$parent->executeCommand("clearWebappCache");
		}	
		return $this->quitOk("I18n synchronization successfully executed");
	}
}
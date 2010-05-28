<?php
class commands_ImportInitData extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "[moduleName1 moduleName2 ... moduleNameN]";
	}

	function getAlias()
	{
		return "iid";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Import init data from modules";
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 0)
		{
			$modules = array();
			foreach (glob("modules/*", GLOB_ONLYDIR) as $module)
			{
				$modules[] = basename($module);
			}
			return $modules;
		}
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Import init data from modules ==");

		$this->loadFramework();

		f_event_EventManager::unregister('f_listener_BackofficeIndexListener');

		$ids = InitDataService::getInstance();
		$ids->setLogger($this);

		if (!f_util_ArrayUtils::isEmpty($params))
		{
			foreach ($params as $moduleName)
			{
				$packageName = 'modules_'.$moduleName;
				if ($this->checkImport($packageName))
				{
					$setImport = true;
					$ids->import($packageName);
					foreach ($ids->getMessages() as $message)
					{
						$type = !is_null($message[1]) ? $message[1] : "error";
						if ($type == "error")
						{
							$setImport = false;
						}
						$this->log($message[0], $type);
					}
					if ($setImport)
					{
						$this->setImport($packageName);
					}
				}
			}
		}
		else
		{
			$modules = ModuleService::getInstance()->getModules();
			foreach ($modules as $module)
			{
				if ($this->checkImport($module))
				{
					$setImport = true;
					$ids->import($module);
					foreach ($ids->getMessages() as $message)
					{
						$type = !is_null($message[1]) ? $message[1] : "error";
						if ($type == "error")
						{
							$setImport = false;
						}
						$this->log($message[0], $type);
					}
					if ($setImport)
					{
						$this->setImport($module);
					}
				}
			}
		}

		$this->quitOk("Init data imported");
	}

	private function checkImport($packageName)
	{
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$initData = $pp->getSettingValue($packageName, 'init-data');
		if (empty($initData))
		{
			return true;
		}
		else
		{
			$this->warnMessage('Package ' . $packageName . ' already initialized  : ' . $initData);
			return false;
		}
	}

	private function setImport($packageName)
	{
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$initData = date_DateFormat::format(date_Calendar::now());
		$pp->setSettingValue($packageName, 'init-data', $initData);
	}
}
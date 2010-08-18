<?php
class commands_GenerateDatabase extends commands_AbstractChangeCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "[moduleName1 moduleName2 ... moduleNameN]";
	}
	
	/**
	 * @return string
	 */
	function getAlias()
	{
		return "gdb";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "generate database";
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
			foreach (glob("modules/*/persistentdocument", GLOB_ONLYDIR) as $path)
			{
				$modules[] = basename(dirname($path));
			}
			return $modules;
		}
		return null;
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Generate database ==");
		
		$this->loadFramework();
		
		// Create if needed.
		$pp = f_persistentdocument_PersistentProvider::getInstance(); 
		if (!$pp->checkConnection())
		{
			$dbInfos = $pp->getConnectionInfos();
			if (isset($dbInfos["host"]))
			{
				$host = $dbInfos["host"];
			}
			else
			{
				$host = "localhost";
			}
			$props = $this->getParent()->getProperties("dbadmin_".$host);
			if (!$pp->createDB($props))
			{
				return $this->quitError("You must create '".$dbInfos["database"]."@".$dbInfos["host"]."' database and give read/write access to '".$dbInfos["user"]."' user.");
			}
		}
		
		// Populate the database.
		list($allowedExtension, $sqlSeparator) = $pp->getScriptFileInfos();
		if ($allowedExtension === null)
		{
			return $this->quitError("Can not generate database for using ".get_class($pp)." for driver");
		}
		$this->setupDatabase($pp, $allowedExtension, $sqlSeparator, $params);
		return null;
	}

	/**
	 * @param f_persistentdocument_PersistentProvider $persistentProvider
	 * @param string $allowedExtension
	 * @param string $sqlSeparator
	 * @param array $modules
	 */
	private function setupDatabase($persistentProvider, $allowedExtension, $sqlSeparator, $modules = array())
	{
		$scripts = array();
		$array = array();
		
		$packages = array();
		if (f_util_ArrayUtils::isNotEmpty($modules))
		{
			foreach ($modules as $module)
			{
				if ($module == 'framework')
				{
					$array[] = f_util_FileUtils::buildFrameworkPath('dataobject');
				}
				else 
				{
					$packages[] = 'modules_' . $module;
				}
			}
		}
		else 
		{
			$packages = ModuleService::getInstance()->getModules();
			$array[] = f_util_FileUtils::buildFrameworkPath('dataobject');
		}
		
		foreach ($packages as $module)
		{
			$array[] = f_util_FileUtils::buildChangeBuildPath(str_replace('_', DIRECTORY_SEPARATOR, $module), 'dataobject');
			$array[] = f_util_FileUtils::buildWebeditPath(str_replace('_', DIRECTORY_SEPARATOR, $module), 'dataobject');
		}
			
		foreach ($array as $dir)
		{
			if (!is_dir($dir))
			{
				continue;
			}

			$dataobjectList = scandir($dir);

			foreach ($dataobjectList as $fileName)
			{
				$filePath = $dir . DIRECTORY_SEPARATOR. $fileName;
				if (!is_dir($filePath))
				{
					$extension = f_util_StringUtils::getFileExtension($fileName, true, 2);
					if ($extension == $allowedExtension)
					{
						$scripts[$fileName] = $filePath;
					}
				}
			}
		}

		if (count($scripts) != 0)
		{
			ksort($scripts);
			foreach ($scripts as $fileName)
			{
				$this->message('Execute SQL Script : '. $fileName);
				$sql = file_get_contents($fileName);

				foreach(explode($sqlSeparator, $sql) as $query)
				{
					$query = trim($query);
					if (empty($query))
					{
						continue;
					}
					try
					{
						$persistentProvider->executeSQLScript($query);
					}
					catch (BaseException $e)
					{
						if ($e->getAttribute('errorcode') != 1060)
						{
							$this->errorMessage(__METHOD__ . ' ERROR : ' . $e->getMessage());
						}
					}
					catch (Exception $e)
					{
						$this->errorMessage(__METHOD__ . ' ERROR : ' . $e->getMessage());
					}
				}
			}
		}
		
		// Generate localized label in f_document.
		$this->message('Update table f_document with supported languages ...');
		foreach (RequestContext::getInstance()->getSupportedLanguages() as $lang) 
		{
			try 
			{
				$persistentProvider->addLang($lang);
			}
			catch (Exception $e)
			{
				Framework::warn($e->getMessage());
			}
		}
		
		// Generate relation_Id.
		$this->message('Compile document relation name ...');
		RelationService::getInstance()->compile();
		
		$this->message("Cleaning Framework cache (f_cache) ...");
		$persistentProvider->clearFrameworkCache();
		
		$this->quitOk("Database generated");
	}
}
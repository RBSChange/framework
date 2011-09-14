<?php
class commands_GenerateDatabase extends commands_AbstractChangeCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "[package1 package2 ... packageN]";
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
		$components = array();
		foreach ($this->getBootStrap()->getProjectDependencies() as $p) 
		{
			/* @var $p c_Package */
			if (is_dir(f_util_FileUtils::buildPath($p->getPath(), 'dataobject')) || 
				is_dir(f_util_FileUtils::buildPath($p->getPath(), 'persistentdocument')))
			{
				$components[] = $p->getKey();
			}
		}	
		return array_diff($components, $params);
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
			return $this->quitError("You must create '".$dbInfos["database"]."@".$dbInfos["host"]."' database and give read/write access to '".$dbInfos["user"]."' user.");
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
		
		if (f_util_ArrayUtils::isNotEmpty($modules))
		{
			foreach ($modules as $module)
			{
				$p = $this->getPackageByName($module);
				
				if ($p->isFramework())
				{
					$array[] = f_util_FileUtils::buildFrameworkPath('dataobject');
				}
				elseif ($p->isModule())
				{
					$array[] = f_util_FileUtils::buildChangeBuildPath($p->getType(), $p->getName(), 'dataobject');
					$array[] = f_util_FileUtils::buildModulesPath($p->getName(), 'dataobject');
				}
			}
		}
		else 
		{
			$array[] = f_util_FileUtils::buildFrameworkPath('dataobject');
			foreach ($this->getBootStrap()->getProjectDependencies() as $p) 
			{
				/* @var $p c_Package */
				if ($p->isModule())
				{
					$array[] = f_util_FileUtils::buildChangeBuildPath($p->getType(), $p->getName(), 'dataobject');
					$array[] = f_util_FileUtils::buildModulesPath($p->getName(), 'dataobject');
				}
				
			}
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
					$extension = f_util_FileUtils::getFileExtension($fileName, true, 2);
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
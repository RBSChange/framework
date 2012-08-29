<?php
class commands_GenerateDatabase extends c_ChangescriptCommand
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
	 * @param integer $completeParamCount the parameters that are already complete in the command line
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return string[] or null
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
		$sm = f_persistentdocument_PersistentProvider::getInstance()->getSchemaManager();
		
		if (!$sm->check())
		{
			$dbInfos = f_persistentdocument_PersistentProvider::getInstance()->getConnectionInfos();
			return $this->quitError("You must create '".$dbInfos["database"]."@".$dbInfos["host"]."' database and give read/write access to '".$dbInfos["user"]."' user.");
		}
		
		// Populate the database.
		$allowedExtension = $sm->getSQLScriptSufixName();
		if ($allowedExtension === null)
		{
			return $this->quitError("Can not generate database for using ".get_class($sm)." for driver");
		}
		$this->setupDatabase($sm, $allowedExtension, $params);
		return null;
	}

	/**
	 * @param change_SchemaManager $schemaManager
	 * @param string $allowedExtension
	 * @param array $modules
	 */
	private function setupDatabase($schemaManager, $allowedExtension, $modules = array())
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
		
		$extensionLength = strlen($allowedExtension);
		
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
					if (substr($fileName, -$extensionLength) === $allowedExtension)
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
				try
				{
					$schemaManager->executeBatch($sql, true);
				}
				catch (Exception $e)
				{
					$this->errorMessage('Execution error on ' . $fileName . ': ' . $e->getCode() . ' ...' . $e->getMessage());
				}
			}
		}
		
		// Generate localized label in f_document.
		$this->message('Update table f_document with supported languages ...');
		foreach (RequestContext::getInstance()->getSupportedLanguages() as $lang) 
		{
			$schemaManager->addLang($lang);
		}
		
		// Generate relation_Id.
		$this->message('Compile document relation name ...');
		RelationService::getInstance()->compile();
		
		$this->message("Cleaning Framework cache (f_cache) ...");
		f_persistentdocument_PersistentProvider::getInstance()->clearFrameworkCache();
		
		$this->quitOk("Database generated");
	}
}
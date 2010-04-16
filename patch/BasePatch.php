<?php
/**
 * @date Tue Jul 24 18:24:44 CEST 2007
 * @author INTcoutL
 */
abstract class patch_BasePatch
{
	/**
	 * @var c_ChangescriptCommand
	 */
	private $command;
	
	private $basePath;

	/**
	 * @param c_ChangescriptCommand $command
	 */
	public final function __construct($command = null)
	{
		$this->command = $command;
		
		switch ($this->getModuleName())
		{
			case 'framework' :
				$this->basePath = FRAMEWORK_HOME;
				break;
			case 'webapp':
				$this->basePath = PROJECT_OVERRIDE;
				break;
			default:
				$this->basePath = f_util_FileUtils::buildRelativePath(AG_MODULE_DIR, $this->getModuleName());
		}
	}

	/**
	 * Logs a message onto the console, using the Phing Task's log method.
	 *
	 * @param String $message
	 * @param Integer $level
	 *
	 * @author intbonjf
	 */
	protected final function log($message, $level = "info")
	{
		if (!is_null($this->command))
		{
			$this->command->log($message, $level);
		}
	}
	
	/**
	 * @param String $message
	 */
	protected final function logError($message)
	{
		if (!is_null($this->command))
		{
			$this->command->log($message, "error");
		}
	}	
	
	/**
	 * @param String $message
	 */
	protected final function logWarning($message)
	{
		if (!is_null($this->command))
		{
			$this->command->log($message, "warn");
		}
	}	

	/**
	 * Executes the patch. This method is called by the Phing Task.
	 *
	 * @author intbonjf
	 */
	public final function executePatch()
	{
		$rq = RequestContext::getInstance();
		$rq->setLang($rq->getDefaultLang());
		$this->echoReadme();
		$this->execute();
		$this->getPersistentProvider()->reset();
		$this->log("Document cache (memory and 'f_cache' database table) has been cleared.");
	}

	/**
	 * Executes the logic of the patch.
	 * This method has to be overriden by the patch developper.
	 */
	public function execute()
	{
		// empty
	}
	
	private function echoReadme()
	{
		echo f_util_FileUtils::read(f_util_FileUtils::buildRelativePath($this->basePath, 'patch', $this->getNumber(), 'README' )) . "\n";
	}
	
	/**
	 * @param String $scriptName
	 */
	protected function executeLocalXmlScript($scriptName)
	{
		$scriptReader = import_ScriptReader::getInstance();
		$scriptReader->execute(FileResolver::getInstance()->setPackageName('modules_' . $this->getModuleName())->setDirectory('patch')->getPath($this->getNumber() . DIRECTORY_SEPARATOR . $scriptName));
	}
	
	/**
	 * @param String $scriptName
	 * @param String $module
	 */
	protected function executeModuleScript($scriptName, $module)
	{
		$scriptReader = import_ScriptReader::getInstance();
		$scriptReader->executeModuleScript($module, $scriptName);
	}

	/**
	 * Executes an SQL file.
	 *
	 * @param String $relativeFilePath
	 *
	 * @author intbonjf
	 */
	protected final function executeSQLFile($relativeFilePath)
	{
		//$this->log("Executing $relativeFilePath");
		if ($relativeFilePath[0] != '/')
		{
			$filePath = f_util_FileUtils::buildAbsolutePath($this->basePath, 'patch', $this->getNumber(), $relativeFilePath);
		}
		else
		{
			$filePath = $relativeFilePath;
		}
		$sql = file_get_contents($filePath);
		foreach(explode(";",$sql) as $query)
		{
			$query = trim($query);
			if (empty($query))
			{
				continue;
			}
			try
			{
				$this->executeSQLQuery($query);
			}
			catch (Exception $e)
			{
				$this->logError($e->getMessage());
			}
		}
	}

	/**
	 * Executes an SQL query.
	 *
	 * @param String $query
	 * @return Integer the number of affected rows
	 * @author intbonjf
	 */
	protected final function executeSQLQuery($query)
	{
		$query = trim($query);
		if (strlen($query) > 0)
		{
			return $this->getPersistentProvider()->executeSQLScript($query);
		}
	}
	
	protected final function executeSQLSelect($query)
	{
		$query = trim($query);
		if (strlen($query) > 0)
		{
			return $this->getPersistentProvider()->executeSQLSelect($query);
		}
	}

	/**
	 * @return f_persistentdocument_PersistentProvider
	 *
	 * @author intbonjf
	 */
	protected final function getPersistentProvider()
	{
		return f_persistentdocument_PersistentProvider::getInstance();
	}

	/**
	 * Begins a new database transaction.
	 *
	 * @author intbonjf
	 */
	protected final function beginTransaction()
	{
		f_persistentdocument_TransactionManager::getInstance()->beginTransaction();
	}

	/**
	 * Commits a previously begun database transaction.
	 *
	 * @author intbonjf
	 */
	protected final function commit()
	{
		f_persistentdocument_TransactionManager::getInstance()->commit();
	}

	/**
	 * Rolls back a previously begun database transaction.
	 *
	 * @param Exception $e
	 *
	 * @author intbonjf
	 */
	protected final function rollBack($e)
	{
		f_persistentdocument_TransactionManager::getInstance()->rollBack($e);
	}

	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	abstract protected function getModuleName();

	/**
	 * Returns the number of the current patch.
	 *
	 * @example 0006
	 *
	 * @return String
	 */
	abstract protected function getNumber();
	
	/**
	 * @return boolean
	 */
	public function isCodePatch()
	{
		return false;
	}
	
	protected final function isInstalled()
	{
		return PatchService::getInstance()->isInstalled($this->getModuleName(), $this->getNumber());
	}
	
	// Static methods.
	
	/**
	 * @param String $module
	 * @param String $author
	 * @return String the patch relative path.
	 */
	public static function createNewPatch($module, $author)
	{
		$date = date('r');
		// TODO: this works only for modules patches, not for framework and webapp ones...
		$version = ModuleService::getInstance()->getModuleVersion('modules_' . $module);
		$patchNumber = self::searchNextPatchNumber($module);
		return self::createPatch($module, $patchNumber, $date, $author, $version);
	}
	
	/**
	 * @param String $module
	 * @param String $patchNumber
	 * @param String $date
	 * @param String $author
	 * @param String $version
	 * @return String the patch relative path.
	 */
	private static function createPatch($module, $patchNumber, $date, $author, $version)
	{
		// Get the patch patch.
		$patchPath = f_util_FileUtils::buildRelativePath(self::getPackagePath($module), 'patch', $patchNumber);

		// Create the directory of new patch
		f_util_FileUtils::mkdir($patchPath);

		// Instance a new object generator based on smarty
		$generator = new builder_Generator('patch');

		// Assign all necessary variable
		$generator->assign_by_ref('moduleName', $module);
		$generator->assign_by_ref('date', $date);
		$generator->assign_by_ref('author', $author);
		$generator->assign_by_ref('version', $version);
		$generator->assign_by_ref('patchNumber', $patchNumber);

		// Execute the template for the README file
		f_util_FileUtils::write($patchPath . DIRECTORY_SEPARATOR . 'README', $generator->fetch('README.tpl'));

		// Execute the template for the install.php file
		f_util_FileUtils::write($patchPath . DIRECTORY_SEPARATOR . 'install.php', $generator->fetch('install.tpl'));
		Resolver::getInstance('class')->appendToAutoloadFile($module . '_patch_' . $patchNumber, $patchPath . DIRECTORY_SEPARATOR . 'install.php');
		
		$lastPatchPath = f_util_FileUtils::buildRelativePath(self::getPackagePath($module), 'patch', 'lastpatch');
		file_put_contents($lastPatchPath, $patchNumber);
		return $patchPath;
	}

	/**
	 * @param String $module
	 * @return String
	 */
	private static function searchNextPatchNumber($module)
	{
		$nextVersion = null;

		// Get the patch directory path.
		$patchPath = f_util_FileUtils::buildRelativePath(self::getPackagePath($module), 'patch');

		// Check if directory Exist
		if (!is_dir($patchPath) )
		{
			f_util_FileUtils::mkdir($patchPath);
			$nextVersion = '0000';
		}
		else
		{
			$patchDirs = scandir($patchPath, 1);
			$lastPatchNumber = 0;
			foreach ($patchDirs as $patchDir)
			{
				if ($patchDir === 'lastpatch' )
				{
					$lastPatchNumber = intval(file_get_contents(f_util_FileUtils::buildPath($patchPath, $patchDir)));
				}
				else if (intval($patchDir) > $lastPatchNumber)
				{
					$lastPatchNumber = intval($patchDir);
				}
			}
			$nextVersion =  str_repeat('0', 4 - strlen(strval($lastPatchNumber + 1))) . strval($lastPatchNumber + 1);
		}

		return $nextVersion;
	}
	
	/**
	 * @param String $shortName the package name : 'news', 'framework', 'webapp'...
	 * @return String
	 */
	private static function getPackagePath($shortName)
	{
		switch ($shortName)
		{
			// Framework.
			case 'framework' :
				$packagePath = FRAMEWORK_HOME;
				break;

			// Webapp.
			case 'webapp' :
				$packagePath = PROJECT_OVERRIDE;
				break;

			// Module.
			default :
				$packagePath = f_util_FileUtils::buildRelativePath(AG_MODULE_DIR, $shortName);
				break;
		}
		return $packagePath;
	}
}
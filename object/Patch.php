<?php
abstract class change_Patch
{
	/**
	 * @var c_ChangescriptCommand
	 */
	private $command;
	
	/**
	 * @var string[]
	 */
	private $parts;	
	
	/**
	 * @param c_ChangescriptCommand $command
	 */
	public final function __construct($command = null)
	{
		$this->command = $command;
		$this->parts = explode('_', get_class($this));		
	}
	
	/**
	 * @return string
	 */
	public function getModuleName()
	{
		return $this->parts[0];
	}
	
	/**
	 * @return string
	 */
	public function getNumber()
	{
		return $this->parts[2];
	}
	
	/**
	 * @return boolean
	 */
	abstract public function isCodePatch();
	
	/**
	 * @return string
	 */
	abstract public function getBasePath();
	
	/**
	 * @return string
	 */
	abstract public function getExecutionOrderKey();
		
	/**
	 * @return boolean
	 */
	public function isInstalled()
	{
		return PatchService::getInstance()->isInstalled($this);
	}
	
	/**
	 * @return string
	 */
	public function getInstallationDate()
	{
		return PatchService::getInstance()->getInstallationDate($this);
	}
	
	/**
	 * @return array|null
	 */
	public function getPreCommandList()
	{
		return null;
	}

	/**
	 * @return array|null
	 */
	public function getPostCommandList()
	{
		return null;
	}	
	
	public final function executePatch()
	{
		$this->logReadme();
		$this->execute();
	}	
	
	/**
	 * Executes the logic of the patch.
	 * This method has to be overriden by the patch developper.
	 */
	abstract protected function execute();

	/**
	 * @param string $message
	 * @param string $level (info|warn|error)
	 */
	protected final function log($message, $level = "info")
	{
		if ($this->command !== null)
		{
			$this->command->log($message, $level);
		}
	}
	
	/**
	 * @param string $message
	 */
	protected final function logError($message)
	{
		$this->log($message, "error");
	}	
	
	/**
	 * @param string $message
	 */
	protected final function logWarning($message)
	{
		$this->log($message, "warn");
	}	

	private function logReadme()
	{
		$message = f_util_FileUtils::read(f_util_FileUtils::buildRelativePath($this->getBasePath(), 'README'));
		$this->log($message);
	}
	
	/**
	 * @param string $scriptName
	 */
	protected function executeLocalXmlScript($scriptName)
	{
		$scriptReader = import_ScriptReader::getInstance();
		$scriptReader->execute(f_util_FileUtils::buildRelativePath($this->getBasePath(), $scriptName));
	}
	
	/**
	 * @param string $scriptName
	 * @param string $module
	 */
	protected function executeModuleScript($scriptName, $module)
	{
		$scriptReader = import_ScriptReader::getInstance();
		$scriptReader->executeModuleScript($module, $scriptName);
	}

	/**
	 * Executes an SQL file.
	 *
	 * @param string $relativeFilePath
	 *
	 * @author intbonjf
	 */
	protected final function executeSQLFile($relativeFilePath)
	{
		//$this->log("Executing $relativeFilePath");
		if ($relativeFilePath[0] != '/')
		{
			$filePath = f_util_FileUtils::buildAbsolutePath($this->getBasePath(), $relativeFilePath);
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
	 * @param string $query
	 * @return integer the number of affected rows
	 */
	protected final function executeSQLQuery($query)
	{
		$query = trim($query);
		if (strlen($query) > 0)
		{
			return $this->getPersistentProvider()->executeSQLScript($query);
		}
	}

	/**
	 * Executes an SQL select.
	 *  
	 * @param string $query
	 * @return array
	 */
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
	 */
	protected final function getPersistentProvider()
	{
		return f_persistentdocument_PersistentProvider::getInstance();
	}

	/**
	 * Begins a new database transaction.
	 */
	protected final function beginTransaction()
	{
		f_persistentdocument_TransactionManager::getInstance()->beginTransaction();
	}

	/**
	 * Commits a previously begun database transaction.
	 */
	protected final function commit()
	{
		f_persistentdocument_TransactionManager::getInstance()->commit();
	}

	/**
	 * Rolls back a previously begun database transaction.
	 *
	 * @param Exception $e
	 */
	protected final function rollBack($e)
	{
		f_persistentdocument_TransactionManager::getInstance()->rollBack($e);
	}
	
	/**
	 * @param string $commandName
	 * @param array $arguments
	 * @return string
	 */
	protected function execChangeCommand($commandName, $arguments = array())
	{
		return f_util_System::execChangeCommand($commandName, $arguments);
	}
	
	/**
	 * For exemple set value to null for remove entry
	 * @param string $path
	 * @param string $value
	 * @return string || false if return value != input value compile-config is required
	 */
	protected final function addProjectConfigurationEntry($path, $value)
	{
		return change_ConfigurationService::getInstance()->addProjectConfigurationEntry($path, $value);
	}
}
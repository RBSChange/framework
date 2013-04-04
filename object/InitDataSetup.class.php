<?php
/**
 * @package framework.object
 */
abstract class object_InitDataSetup
{
	abstract public function install();
	
	/**
	 * @return String
	 */
	private function getModule()
	{
		$info = explode('_', get_class($this)); 
		return $info[0];
	}

	/**
	 * Returns an array of packages names that are required to install the data.
	 * Please override this method in the initData.php of your module if needed.
	 *
	 * @return Array<String>
	 */
	public function getRequiredPackages()
	{
		return array();
	}

	/**
	 * @param String $databaseName
	 * @return f_persistentdocument_PersistentProvider
	 */
	protected function getPersistentProvider($databaseName = 'default')
	{
		return f_persistentdocument_PersistentProvider::getInstance($databaseName);
	}

	/**
	 * @return f_persistentdocument_TransactionManager
	 */
	protected function getTransactionManager()
	{
		return f_persistentdocument_TransactionManager::getInstance();
	}

	/**
	 * @var Array<String>
	 */
	private $messages = array();


	/**
	 * Registers a message to log.
	 *
	 * @param String $message
	 * @param Integer $level [Project::MSG_INFO | Project::MSG_WARN | Project::MSG_ERR]
	 */
	protected final function log($message, $level = "info")
	{
		$this->addMessage($message, $level);
	}

	/**
	 * Registers a message to log.
	 *
	 * @param String $message
	 * @param integer $level  [Project::MSG_INFO | Project::MSG_WARN | Project::MSG_ERR]
	 */
	protected function addMessage($message, $level = null)
	{
		$this->messages[] = array($message, $level);
	}

	/**
	 * Registers an error message to log.
	 *
	 * @param String $message
	 */
	protected function addError($message)
	{
		$this->addMessage($message, "error");
	}

	/**
	 * Registers a warning message to log.
	 *
	 * @param String $message
	 */
	protected function addWarning($message)
	{
		$this->addMessage($message, "warn");
	}

	/**
	 * Registers an information message to log.
	 *
	 * @param String $message
	 */
	protected function addInfo($message)
	{
		$this->addMessage($message, "info");
	}

	/**
	 * Indicates whether there are messages (true) or not (false).
	 *
	 * @return boolean
	 */
	public function hasMessage()
	{
		return count($this->messages) > 0;
	}

	/**
	 * Returns the error messages as an array.
	 *
	 * @return Array<String>
	 */
	public function getMessages()
	{
		return $this->messages;
	}

	/**
	 * @param String $scriptName
	 * @param String $module
	 * @return Boolean
	 */
	protected final function executeModuleScript($scriptName, $module = null)
	{
		try
		{
			$scriptReader = import_ScriptReader::getInstance();
			if ($module === null)
			{
				$module = $this->getModule();
			}
			$scriptReader->executeModuleScript($module, $scriptName);
			return true;
		}
		catch (Exception $e)
		{
			$this->addError("ERROR: " . $e->getMessage());
			Framework::exception($e);
			return false;
		}
	}
	
	/**
	 * @example set value to null for remove entry
	 * @param string $path
	 * @param string $value
	 * @param string $profile
	 * @return string || false if return value != input value compile-config is required
	 */
	protected final function addProjectConfigurationEntry($path, $value, $profile = null)
	{
		return change_ConfigurationService::getInstance()->addProjectConfigurationEntry($path, $value, $profile);
	}
	
	/**
	 * @param string $originalClass
	 * @param string $newClass
	 * @return boolean
	 */
	protected final function addInjectionInProjectConfiguration($originalClass, $newClass)
	{
		$nnsName = Framework::getConfigurationValue('injection/' . $originalClass, $newClass);
		if ($nnsName != $originalClass && $nnsName != $newClass)
		{
			$this->addWarning($nnsName . ' must extend ' . $newClass . ' !');
			return false;
		}
		else
		{
			$this->addProjectConfigurationEntry('injection/' . $originalClass, $newClass);
			return true;
		}
	}
}
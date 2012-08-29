<?php
/**
 * @package framework.object
 */
abstract class object_InitDataSetup
{
	abstract public function install();
	
	/**
	 * @return string
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
	 * @return f_persistentdocument_PersistentProvider
	 */
	protected function getPersistentProvider()
	{
		return f_persistentdocument_PersistentProvider::getInstance();
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
	 * @param string $message
	 * @param string $level [info | warn | error]
	 */
	protected final function log($message, $level = "info")
	{
		$this->addMessage($message, $level);
	}

	/**
	 * Registers a message to log.
	 *
	 * @param string $message
	 * @param string $level [info | warn | error]
	 */
	protected function addMessage($message, $level = null)
	{
		$this->messages[] = array($message, $level);
	}

	/**
	 * Registers an error message to log.
	 *
	 * @param string $message
	 */
	protected function addError($message)
	{
		$this->addMessage($message, "error");
	}

	/**
	 * Registers a warning message to log.
	 *
	 * @param string $message
	 */
	protected function addWarning($message)
	{
		$this->addMessage($message, "warn");
	}

	/**
	 * Registers an information message to log.
	 *
	 * @param string $message
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
	 * @param string $scriptName
	 * @param string $module
	 * @return boolean
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
	 * @param string $path
	 * @param string $value Set value to null for remove entry
	 * @return string || false if return value != input value compile-config is required
	 */
	protected function addProjectConfigurationEntry($path, $value)
	{
		return config_ProjectParser::addProjectConfigurationEntry($path, $value);
	}
	
	/**
	 * @param string $originalClass
	 * @param string $newClass
	 * @param string $section [class|document]
	 * @return boolean
	 */
	protected final function addInjectionInProjectConfiguration($originalClass, $newClass, $section = 'class')
	{
		$nnsName = Framework::getConfigurationValue('injection/' . $section . '/' . $originalClass, $newClass);
		if ($nnsName != $originalClass && $nnsName != $newClass)
		{
			$this->addWarning($nnsName . ' must extend ' . $newClass . ' !');
			return false;
		}
		else
		{
			$this->addProjectConfigurationEntry('injection/' . $section . '/' . $originalClass, $newClass);
			return true;
		}
	}
}
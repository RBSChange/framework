<?php
class InitDataService extends BaseService
{
	/**
	 * @var InitDataService
	 */
	private static $instance;

	/**
	 * @var Array<String>
	 */
	private $importedPackages = array();

	/**
	 * @var Array<Array>
	 */
	private $messageArray = array();

	/**
	 * @var Task
	 */
	private $logger;


	/**
	 * Constructor of InitDataService
	 */
	protected function __construct()
	{
	}


	/**
	 * @return InitDataService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Imports the initialization data for the given $packageName.
	 *
	 * @param String $packageName
	 * @param Boolean $clearMessages
	 */
	public function import($packageName, $clearMessages = true)
	{
		if ($clearMessages === true)
		{
			$this->clearMessages();
		}
		if (!$this->isPackageImported($packageName) && !isset($this->currentlyImportedPackages[$packageName]))
		{
			$this->currentlyImportedPackages[$packageName] = true;
			if (substr($packageName, 0, 8) == 'modules_')
			{
				$this->log("Importing init data for package '$packageName'...");
				if ($this->importDataForModule(substr($packageName, 8)))
				{
					$this->addToImportedPackagesList($packageName);
				}
				else
				{
					$this->addMessage($packageName . ' has no setup file.');	
				}
			}
			unset($this->currentlyImportedPackages[$packageName]);
		}
		else
		{
			$this->log("Init data for package '$packageName' already imported.");
		}
	}


	/**
	 * Clears the messages list.
	 */
	private function clearMessages()
	{
		$this->messageArray = array();
	}


	/**
	 * Adds a message to the messages list.
	 *
	 * @param String $message
	 * @param Integer $level
	 */
	private function addMessage($message, $level = "error")
	{
		$this->messageArray[] = array($message, $level);
	}


	/**
	 * Returns an array containing all the messages.
	 *
	 * @return Array<Array>
	 */
	public function getMessages()
	{
		return $this->messageArray;
	}


	/**
	 * Imports the initialization data for the given $moduleName.
	 *
	 * @param String $moduleName
	 */
	private function importDataForModule($moduleName)
	{
		$result = false;
		
		$file = f_util_FileUtils::buildModulesPath($moduleName, 'setup', 'initData.php');
		if ( is_readable($file) )
		{
			$this->doImport($file, $this->getSetupClassName($moduleName, false));
			$result = true;
		}

		$file = f_util_FileUtils::buildOverridePath('modules', $moduleName, 'setup', 'initData.php');
		if ( is_readable($file) )
		{
			$this->doImport($file, $this->getSetupClassName($moduleName, true));
			$result = true;
		}
		return $result;
	}


	/**
	 * Returns the name of the class that import the data for the given $moduleName.
	 *
	 * @param String $moduleName
	 * @param Boolean $inWebapp if true, indicates that we are searching for the one that is inside the webapp.
	 * @return String The classname.
	 */
	protected function getSetupClassName($moduleName, $inWebapp)
	{
		return $moduleName . ($inWebapp ? '_ExtendedSetup' : '_Setup');
	}


	/**
	 * Imports the initialization data.
	 *
	 * @param String $file
	 * @param String $className
	 */
	private function doImport($file, $className)
	{
		require_once($file);
		try
		{
			$setup = new $className();
			if ($setup instanceof object_InitDataSetup)
			{
				if (count($requiredPackageArray = $setup->getRequiredPackages()) > 0)
				{
					foreach ($requiredPackageArray as $requiredPackage)
					{
						$this->log("Found dependency with package '$requiredPackage'.");
						$this->import($requiredPackage, false);
					}
				}
				$setup->install();
				if ($setup->hasMessage())
				{
					foreach ($setup->getMessages() as $message)
					{
						$this->addMessage($message[0], !is_null($message[1]) ? $message[1] : "error");
					}
				}
			}
			else
			{
				$setup->install();
			}
		}
		catch (ValidationException $e)
		{
			$this->addMessage($e->getMessage() . ' (' . $file . ') ', "error");
			return false;
		}
		return true;
	}


	/**
	 * @param String $packageName
	 */
	protected function addToImportedPackagesList($packageName)
	{
		$this->importedPackages[] = $packageName;
	}


	/**
	 * @param String $packageName
	 * @return Boolean
	 */
	protected function isPackageImported($packageName)
	{
		if (!in_array($packageName, $this->importedPackages))
		{
			$pp = f_persistentdocument_PersistentProvider::getInstance();
			$initData = $pp->getSettingValue($packageName, 'init-data');
			if (empty($initData))
			{
		  	  return false;
			}
			$this->addToImportedPackagesList($packageName);
			return true;
		}
		return true;
	}


	/**
	 * Clears the internal list of imported packages.
	 */
	public function clearImportedPackagesList()
	{
		$this->importedPackages = array();
		$this->clearMessages();
	}


	/**
	 * Sets the logger to use to log messages. Generally, $logger is a Phing Task
	 * instance, but it can be any object that implements a log($message, $level) method.
	 *
	 * @param Task $logger
	 */
	public function setLogger($logger)
	{
		if (!f_util_ClassUtils::methodExists($logger, 'log'))
		{
			throw new Exception('Bad Logger');
		}
		$this->logger = $logger;
	}


	/**
	 * Logs a message.
	 *
	 * @param String $message
	 * @param Integer $level
	 */
	private function log($message)
	{
		if (!is_null($this->logger))
		{
			$this->logger->log($message, "");
		}
	}
}
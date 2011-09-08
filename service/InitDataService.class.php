<?php
class InitDataService extends BaseService
{
	/**
	 * @var InitDataService
	 */
	private static $instance;


	/**
	 * @var c_ChangescriptCommand
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
	 * @param string $message
	 * @param string $level
	 */
	public function log($message, $level = "info")
	{
		if ($this->logger !== null)
		{
			$this->logger->log($message, $level);
		}
	}


	/**
	 * Imports the initialization data for the given $packageName.
	 *
	 * @param String $packageName
	 * @param Boolean $clearMessages
	 */
	public function import($packageName)
	{
		if (!$this->isPackageImported($packageName))
		{
			if (substr($packageName, 0, 8) == 'modules_')
			{
				$this->log("Importing init data for package '$packageName'...");
				if ($this->importDataForModule(substr($packageName, 8)))
				{
					$this->setImportedDate($packageName);
				}
				else
				{
					$this->log($packageName . ' has no setup file.', "error");	
				}
			}
		}
		else
		{
			$this->log("Init data for package '$packageName' already imported.");
		}
	}
	
	/**
	 * @param string $packageName
	 * @param string $date
	 */
	public function setImportedDate($packageName, $date = null)
	{
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try 
		{
			$tm->beginTransaction();
			if ($date == null) {$date = date_Formatter::format(date_Calendar::now());}
			$tm->getPersistentProvider()->setSettingValue($packageName, 'init-data', $date);
			
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			return null;	
		}
		return $date;
	}
	/**
	 * @param string $packageName
	 * @param string $date
	 */	
	public function clearImportedDate($packageName)
	{
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try 
		{
			$tm->beginTransaction();
			$tm->getPersistentProvider()->setSettingValue($packageName, 'init-data', null);
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}
	}
	
	/**
	 * @param string $packageName
	 * @return string
	 */
	public function getImportedDate($packageName)
	{
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$initData = $pp->getSettingValue($packageName, 'init-data');
		return (empty($initData)) ? null : $initData;
	}
	
	/**
	 * @param string $packageName
	 * @return boolean
	 */
	public function isPackageImported($packageName)
	{
		return $this->getImportedDate($packageName) !== null;
	}
	
	/**
	 * Imports the initialization data for the given $moduleName.
	 * @param String $moduleName
	 * @return boolean
	 */
	private function importDataForModule($moduleName)
	{
		$result = false;
		$file = f_util_FileUtils::buildModulesPath($moduleName, 'setup', 'initData.php');
		if (is_readable($file) )
		{
			$setupClassName = $moduleName . '_Setup';
			$result = $this->doImport($file, $setupClassName);;
		}
		return $result;
	}

	/**
	 * Imports the initialization data.
	 *
	 * @param String $file
	 * @param String $className
	 * @return boolean
	 */
	private function doImport($file, $className)
	{
		require_once($file);
		try
		{
			if (!class_exists($className, false)) 
			{
				throw new Exception('Class not found: ' . $className);
			}
			$setup = new $className();
			if ($setup instanceof object_InitDataSetup)
			{
				if (count($requiredPackageArray = $setup->getRequiredPackages()) > 0)
				{
					foreach ($requiredPackageArray as $requiredPackage)
					{
						if (!$this->isPackageImported($requiredPackage))
						{
							$this->log("Import required dependency package '$requiredPackage'.");
							$this->import($requiredPackage, false);
						}
						else
						{
							$this->log("Dequired dependency package '$requiredPackage' Already imported.");
						}
					}
				}
				$setup->install();
				if ($setup->hasMessage())
				{
					foreach ($setup->getMessages() as $message)
					{
						$this->log($message[0], !is_null($message[1]) ? $message[1] : "error");
					}
				}
			}
			elseif (f_util_ClassUtils::methodExists($setup, 'install'))
			{
				$setup->install();
			}
			else
			{
				throw new Exception('Class: ' . $className . ' required "install" function.');
			}
		}
		catch (Exception $e)
		{
			$this->log($e->getMessage() . ' (' . $file . ') ', "error");
			return false;
		}
		return true;
	}
}
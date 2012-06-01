<?php
umask(0002);
class Framework
{
	/**
	 * The project config compiled
	 */
	private static $config = null;

	/**
	 * @var Zend_Log
	 */
	private static $log;

	/**
	 * @return string (DEBUG, INFO, NOTICE, WARN, ERR, ALERT, EMERG) 
	 */
	public static function getLogLevelName()
	{
		return LOGGING_LEVEL;
	}
	
	/**
	 * @return integer
	 */
	public static function getLogPriority()
	{
		return LOGGING_PRIORITY;
	}
	
	/**
	 * @return Zend_Log
	 */
	public static function getZendLog()
	{
		if (self::$log === null)
		{
			self::$log = new Zend_Log();
			$filePath = f_util_FileUtils::buildLogPath('application.log');
			if (!file_exists($filePath))
			{
				f_util_FileUtils::mkdir(dirname($filePath));
			}
			$writer = new Zend_Log_Writer_Stream($filePath);
			$writer->setFormatter(new Zend_Log_Formatter_Simple('%timestamp% [%priorityName%] %sessionId%: %message%' . PHP_EOL));
			self::$log->addWriter($writer);
			
			$filter = new Zend_Log_Filter_Priority(LOGGING_PRIORITY);
			self::$log->addFilter($filter);
			self::$log->setEventItem('sessionId' , '');
			self::$log->setTimestampFormat('Y-m-d H:i:s');
			self::$log->registerErrorHandler();
		}	
		return self::$log;
	}
	
	public static function registerLogErrorHandler()
	{
		self::getZendLog()->registerErrorHandler();
	}
	
	public static function sessionStarted($id)
	{
		self::$log->setEventItem('sessionId' , '(' . $id . ')');
		//self::info(__METHOD__);
	}
	/**
	 * @param string $message
	 * @param integer $priority elementof {Zend_Log::DEBUG, Zend_Log::INFO, Zend_Log::NOTICE, Zend_Log::WARN, 
	 * 	Zend_Log::ERR, Zend_Log::EMERG}
	 */
	public static function log($message, $priority)
	{
		self::getZendLog()->log($message, $priority);
	}

	public static function debug($message)
	{
		self::getZendLog()->log($message, Zend_Log::DEBUG);
	}

	public static function info($message)
	{
		self::getZendLog()->log($message, Zend_Log::INFO);
	}

	public static function warn($message)
	{
		self::getZendLog()->log($message, Zend_Log::WARN);
	}

	public static function error($message)
	{
		self::getZendLog()->log($message, Zend_Log::ERR);
	}

	public static function exception($e)
	{
		self::getZendLog()->log(get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString(), Zend_Log::ALERT);
	}

	public static function fatal($message)
	{
		self::getZendLog()->log($message, Zend_Log::EMERG);
	}

	/**
	 * @return Boolean DEBUG if debug log is enabled
	 */
	public static function isDebugEnabled()
	{
		return LOGGING_PRIORITY >= Zend_Log::DEBUG;
	}

	/**
	 * @return Boolean true if INFO log is enabled
	 */
	public static function isInfoEnabled()
	{
		return LOGGING_PRIORITY >= Zend_Log::INFO;
	}
	
	/**
	 * @return Boolean true if WARN log is enabled
	 */
	public static function isWarnEnabled()
	{
		return LOGGING_PRIORITY >= Zend_Log::WARN;
	}

	/**
	 * @return Boolean true if ERR log is enabled
	 */
	public static function isErrorEnabled()
	{
		return LOGGING_PRIORITY >= Zend_Log::ERR;
	}

	/**
	 * @return Boolean true if EMERG log is enabled
	 */
	public static function isFatalEnabled()
	{
		return LOGGING_PRIORITY >= Zend_Log::EMERG;
	}
	
	/**
	 * @var Zend_Log
	 */
	private static $benchLog;
	
	/**
	 * @var Float[]
	 */
	private static $benchSteps = array();
	
	/**
	 * @var Integer
	 */
	private static $benchStepsIndex = -1;
	
	/**
	 * @return boolean true if NOTICE log is enabled
	 */
	public static function isBenchEnabled()
	{
		return self::getZendBenchLog() !== false;
	}	

	
	/**
	 * Framework::startBench(); // begin bench process
	 * $brands = $this->getBrandsList();
	 * Framework::bench("getBrandList"); // debug time between this call and last call
	 * foreach ($brands as $brand)
	 * {
	 *  $label = $brand->getLabel();
	 *  $index[strtolower($label[0])][$label] = $brand;
	 * }
	 * Framework::endBench("getBrandList processing"); // debug time between this call and last call and end bench process
	 * @param String $msg
	 */
	public static function startBench($start = null)
	{
		self::$benchSteps[] = ($start === null) ? microtime(true) : $start;
		self::$benchStepsIndex++;
	}

	/**
	 * @return Zend_Log
	 */
	private static function getZendBenchLog()
	{
		if (self::$benchLog === null)
		{
			if (self::getConfigurationValue('bench/enabled', 'false') !== 'true')
			{
				self::$benchLog = false;
				return self::$benchLog;
			}
			
			self::$benchLog = new Zend_Log();
			$filePath = f_util_FileUtils::buildLogPath('bench.log');
			if (!file_exists($filePath))
			{
				f_util_FileUtils::mkdir(dirname($filePath));
			}
			$writer = new Zend_Log_Writer_Stream($filePath);
			$writer->setFormatter(new Zend_Log_Formatter_Simple("%id%\t%stepTime%\t%currentTime%\t%diffTime%\t%memory%\t%message%" . PHP_EOL));
			self::$benchLog->addWriter($writer);
			self::$benchLog->setEventItem('id' , rand() . "\t" . microtime(true));
		}
		return self::$benchLog;
	}
	
	/**
	 * @see Framework::startBench($msg)
	 * @param String $msg
	 */
	public static function bench($msg)
	{
		$benchLog = self::getZendBenchLog();
		
		if ($benchLog !== false)
		{
			$newStep = microtime(true);
			$benchLog->log($msg, Zend_Log::NOTICE, array('currentTime' => $newStep, 
				'stepTime' => self::$benchSteps[self::$benchStepsIndex],
				'diffTime' => $newStep - self::$benchSteps[self::$benchStepsIndex],
				'memory' => memory_get_usage(),
			));
			self::$benchSteps[self::$benchStepsIndex] = $newStep;
		}
	}

	/**
	 * @see Framework::startBench($msg)
	 * @param String $msg
	 */
	public static function endBench($msg = null)
	{
		if (!is_null($msg))
		{
			self::bench($msg);
		}
		array_pop(self::$benchSteps);
		self::$benchStepsIndex--;
	}

	
	/**
	 * @return String
	 */
	public static function getVersion()
	{
		return FRAMEWORK_VERSION;
	}

	/**
	 * @see project config and DEVELOPMENT_MODE constant
	 * @return Boolean
	 */
	public static function inDevelopmentMode()
	{
		return DEVELOPMENT_MODE;
	}
	
	/**
	 * @return string
	 */
	public static function getProfile()
	{
		if (file_exists(PROJECT_HOME.'/profile'))
		{
			return trim(file_get_contents(PROJECT_HOME.'/profile'));
		}
		else
		{
			return 'default';
		}
	}

	/**
	 * @return Boolean
	 */
	public static function isSiteEnabled()
	{
		$siteDisabledFlag = f_util_FileUtils::buildProjectPath("site_is_disabled");
		return !file_exists($siteDisabledFlag);
	}
	
	/**
	 * @return String
	 */
	public static function getBaseUrl()
	{
		$webSite = website_WebsiteService::getInstance()->getCurrentWebsite();
		if ($webSite->isContextLangAvailable())
		{
			return $webSite->getProtocol() . '://'. $webSite->getDomain();	
		}
		return $webSite->getProtocol() . '://'. $webSite->getVoDomain();
	}

	/**
	 * @return String
	 */
	public static function getUIBaseUrl()
	{
		return self::getUIProtocol() . '://'.self::getUIDefaultHost();
	}

	/**
	 * @return String
	 */
	public static function getUIProtocol()
	{
		return DEFAULT_UI_PROTOCOL;
	}

	/**
	 * @return String
	 */
	public static function getUIDefaultHost()
	{
		$general = self::$config['general'];
		return isset($general['server-fqdn']) ? $general['server-fqdn'] : $_SERVER['HTTP_HOST'];
	}

	/**
	 * @return string
	 */
	public static function getDefaultSenderHost()
	{
		if (!defined('DEFAULT_SENDER_HOST'))
		{
			define('DEFAULT_SENDER_HOST', self::getUIDefaultHost());
		}
		return DEFAULT_SENDER_HOST;
	}

	/**
	 * @return string
	 */
	public static function getDefaultNoReplySender()
	{
		if (!defined('NOREPLY_DEFAULT_EMAIL'))
		{
			define('NOREPLY_DEFAULT_EMAIL', 'noreply@' . self::getDefaultSenderHost());
		}
		return NOREPLY_DEFAULT_EMAIL;
	}

	/**
	 * Return an array with configuration of Framework
	 */
	public static function getAllConfiguration()
	{
		return self::$config;
	}


	/**
	 * Return true if the $path configuration exist
	 * @param String $path
	 */
	public static function hasConfiguration($path)
	{
		$current = self::$config;
		foreach (explode('/', $path) as $part)
		{
			if (!isset($current[$part]))
			{
				return false;
			}
			$current = $current[$part];
		}
		return true;
	}

	/**
	 * Return an array with part of configuration of Framework
	 * or throw a Exception if the $path configuration does not exist
	 * @param String $path
	 * @param Boolean $strict
	 * @throws Exception if the $path configuration does not exist
	 * @return String | false if the path was not founded and strict value if false
	 */
	public static function getConfiguration($path, $strict = true)
	{
		$current = self::$config;
		foreach (explode('/', $path) as $part)
		{
			if (!isset($current[$part]))
			{
				if ($strict)
				{
					throw new Exception('Part of configuration ' . $part . ' not found.');
				}
				return false;
			}
			$current = $current[$part];
		}
		return $current;
	}

	/**
	 * Return an array with part of configuration of Framework
	 * or null if the $path configuration does not exist
	 * @param String $path
	 * @param String $defaultValue
	 * @return mixed | null
	 */
	public static function getConfigurationValue($path, $defaultValue = null)
	{
		$value = self::getConfiguration($path, false);
		if ($value === false || (is_string($value) && f_util_StringUtils::isEmpty($value)) || (is_array($value) && f_util_ArrayUtils::isEmpty($value)))
		{
			return $defaultValue;
		}
		return $value;
	}

	/**
	 * Only used by ModuleGenerator. TODO: remove it.
	 * @param string $packageName 'modules_xxxx'
	 * @param array $infos
	 */
	public static function addPackageConfiguration($packageName, $infos)
	{
		if (isset(self::$config) && isset(self::$config['packageversion']))
		{
			self::$config['packageversion'][$packageName] = $infos;
		}
		else
		{
			throw new Exception('Framework configuration not loaded');
		}
	}

	/**
	 * Load the framework configuration. Use the file php auto generated in cache/config
	 * You can specify an environnement to load a particular config file
	 * @param Boolean $onlyConfig
	 */
	public static function loadConfiguration($onlyConfig = false)
	{
		// If configuration not yet loaded, load it
		if (self::$config === null)
		{
			$cacheConfigDir = PROJECT_HOME . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'config';	
			
			if (!$onlyConfig)
			{
				$cacheDefinesFile = $cacheConfigDir."/project.defines.php";
				if (!is_file($cacheDefinesFile))
				{
					throw new Exception("Could not find $cacheDefinesFile. You must compile your configuration.");
				}
				require($cacheDefinesFile);
			}
			
			$cacheFile = $cacheConfigDir."/project.php";
			if (!is_file($cacheFile))
			{
				throw new Exception("Could not find $cacheFile. You must compile your configuration.");
			}
			require($cacheFile);
		}
	}

	/**
	 * @param String $env
	 */
	public static function reloadConfiguration()
	{
		if (self::$config !== null)
		{
			self::$config = null;
			self::loadConfiguration(true);
			// TODO: inverse dependences
			ModuleService::clearInstance();
			generator_PersistentModel::reloadModels();
		}
	}
	
	/**
	 * @return array
	 */
	public static function getHttpClientConfig()
	{
		return self::$config['http'];
	}
	
	public static function registerAutoload()
	{
		spl_autoload_register(array(__CLASS__, "autoload"));
	}
	
    /**
     * @param string $className
     * @return void
     */
    public static function autoload($className)
    {
    	$basePath = PROJECT_HOME . '/build/autoload';
    	if (!is_dir($basePath))
    	{
    		die('Please execute '. CHANGE_COMMAND . ' compile-autoload');
    	}
    	$path =  $basePath . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className) . DIRECTORY_SEPARATOR . "to_include";
    	if (is_readable($path)) {require_once ($path);}
    	
    	if (strpos($className, 'Zend_') === 0 && defined("ZEND_FRAMEWORK_PATH"))
    	{
    		$path = ZEND_FRAMEWORK_PATH . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    		if (is_readable($path)) {require_once ($path);}	
    	}
    }
}

// Load configuration
Framework::loadConfiguration();
if (Framework::inDevelopmentMode()) {error_reporting(E_ALL);}

ini_set('include_path', ZEND_FRAMEWORK_PATH . (defined('INCLUDE_PATH') ? PATH_SEPARATOR . INCLUDE_PATH : ''));

Framework::registerAutoload();

ini_set('arg_separator.output',      '&amp;');
ini_set('display_errors',            1);
ini_set('magic_quotes_runtime',      0);



Framework::registerLogErrorHandler();

// Set the locale.
$localResult = setlocale(LC_ALL, 'en_US.UTF-8');

// Set GMT TimeZone
date_default_timezone_set('GMT');

if (Framework::inDevelopmentMode())
{
	change_InjectionService::getInstance()->update();
}
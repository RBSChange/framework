<?php
umask(0002);
class Framework
{
	/**
	 * @param string $message
	 */
	public static function debug($message)
	{
		change_LoggingService::getInstance()->debug($message);
	}

	/**
	 * @param string $message
	 */
	public static function info($message)
	{
		change_LoggingService::getInstance()->info($message);
	}

	/**
	 * @param string $message
	 */
	public static function warn($message)
	{
		change_LoggingService::getInstance()->warn($message);
	}

	/**
	 * @param string $message
	 */
	public static function error($message)
	{
		change_LoggingService::getInstance()->error($message);
	}

	/**
	 * @param Exception $e
	 */
	public static function exception($e)
	{
		change_LoggingService::getInstance()->exception($e);
	}

	/**
	 * @param string $message
	 */
	public static function fatal($message)
	{
		change_LoggingService::getInstance()->fatal($message);
	}
	
	/**
	 * @param string $message
	 */
	public static function deprecated($message)
	{
		if (self::inDevelopmentMode())
		{
			trigger_error($message, E_USER_DEPRECATED);
		}
	}

	/**
	 * @return boolean DEBUG if debug log is enabled
	 */
	public static function isDebugEnabled()
	{
		return LOGGING_PRIORITY >= Zend_Log::DEBUG;
	}

	/**
	 * @return boolean true if INFO log is enabled
	 */
	public static function isInfoEnabled()
	{
		return LOGGING_PRIORITY >= Zend_Log::INFO;
	}
	
	/**
	 * @return boolean true if WARN log is enabled
	 */
	public static function isWarnEnabled()
	{
		return LOGGING_PRIORITY >= Zend_Log::WARN;
	}

	/**
	 * @return boolean true if ERR log is enabled
	 */
	public static function isErrorEnabled()
	{
		return LOGGING_PRIORITY >= Zend_Log::ERR;
	}

	/**
	 * @return boolean true if EMERG log is enabled
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
	 * Example of usage:
	 * 		Framework::startBench(); // begin bench process
	 * 		$brands = $this->getBrandsList();
	 * 		Framework::bench("getBrandList"); // debug time between this call and last call
	 * 		foreach ($brands as $brand)
	 * 		{
	 * 			$label = $brand->getLabel();
	 *			$index[strtolower($label[0])][$label] = $brand;
	 *		}
	 * 		Framework::endBench("getBrandList processing"); // debug time between this call and last call and end bench process
	 * @param string $msg
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
	 * @param string $msg
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
	 * @param string $msg
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
	 * @return string
	 */
	public static function getVersion()
	{
		return FRAMEWORK_VERSION;
	}

	/**
	 * @see project config and DEVELOPMENT_MODE constant
	 * @return boolean
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
		return change_ConfigurationService::getInstance()->getCurrentProfile();
	}

	/**
	 * @return boolean
	 */
	public static function isSiteEnabled()
	{
		$siteDisabledFlag = f_util_FileUtils::buildProjectPath("site_is_disabled");
		return !file_exists($siteDisabledFlag);
	}
	
	/**
	 * @return string
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
	 * @return string
	 */
	public static function getUIBaseUrl()
	{
		return self::getUIProtocol() . '://'.self::getUIDefaultHost();
	}

	/**
	 * @return string
	 */
	public static function getUIProtocol()
	{
		return DEFAULT_UI_PROTOCOL;
	}

	/**
	 * @return string
	 */
	public static function getUIDefaultHost()
	{
		$general = change_ConfigurationService::getInstance()->getConfiguration('general');
		return isset($general['server-fqdn']) ? $general['server-fqdn'] : $_SERVER['HTTP_HOST'];
	}
	
	/**
	 * @return string
	 */
	public static function getDefaultNoReplySender()
	{
		return self::getConfigurationValue('modules/notification/noreplySender', 'noreply@' . self::getUIDefaultHost());
	}
	
	/**
	 * @return string
	 */
	public static function getDefaultSenderHost()
	{
		list(, $host) = explode('@', self::getDefaultNoReplySender());
		return $host;
	}

	/**
	 * Return true if the $path configuration exist.
	 * 
	 * @param string $path
	 */
	public static function hasConfiguration($path)
	{
		return change_ConfigurationService::getInstance()->hasConfiguration($path);
	}

	/**
	 * Return an array with part of project configuration.
	 * 
	 * @param string $path
	 * @param boolean $strict
	 * @throws Exception if the $path configuration does not exist and $strict is set to true
	 * @return string | false if the path was not found and strict value is false
	 */
	public static function getConfiguration($path, $strict = true)
	{
		return change_ConfigurationService::getInstance()->getConfiguration($path, $strict);
	}

	/**
	 * Return an array with part of configuration of Framework
	 * or null if the $path configuration does not exist.
	 * 
	 * @param string $path
	 * @param string $defaultValue
	 * @return mixed | null
	 */
	public static function getConfigurationValue($path, $defaultValue = null)
	{
		return change_ConfigurationService::getInstance()->getConfigurationValue($path, $defaultValue);
	}
	
	/**
	 * @return array
	 */
	public static function getHttpClientConfig()
	{
		return change_ConfigurationService::getInstance()->getConfiguration('http');
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
		if (is_readable($path)) {require_once $path;}
		
		if (strpos($className, 'Zend_') === 0 && defined("ZEND_FRAMEWORK_PATH"))
		{
			$path = ZEND_FRAMEWORK_PATH . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
			if (is_readable($path)) {require_once $path;}	
		}
	}
	
	// Deprecated
	
	/**
	 * @deprecated
	 */
	public static function log($message, $priority)
	{
		change_LoggingService::getInstance()->fatal("Invalid call of Framework::log('$message', $priority).");
	}

	/**
	 * @deprecated use change_ConfigurationService::getAllConfiguration()
	 */
	public static function getAllConfiguration()
	{
		return change_ConfigurationService::getInstance()->getAllConfiguration();
	}

	/**
	 * @deprecated
	 */
	public static function addPackageConfiguration($packageName, $infos)
	{
		$cs = change_ConfigurationService::getInstance();
		$config = $cs->getAllConfiguration();
		if ($config != null && isset($config['packageversion']))
		{
			$cs->addVolatileProjectConfigurationNamedEntry('packageversion/' . $packageName, $infos);
		}
		else
		{
			throw new Exception('Framework configuration not loaded');
		}
	}

	/**
	 * @deprecated use change_ConfigurationService::loadConfiguration()
	 */
	public static function loadConfiguration($onlyConfig = false)
	{
		change_ConfigurationService::getInstance()->loadConfiguration($onlyConfig);
	}

	/**
	 * @deprecated use change_ConfigurationService::loadConfiguration()
	 */
	public static function reloadConfiguration()
	{
		change_ConfigurationService::getInstance()->loadConfiguration();
	}
}

// Load configuration
Framework::registerAutoload();
change_ConfigurationService::getInstance()->loadConfiguration();

if (Framework::inDevelopmentMode()) {error_reporting(E_ALL);}

ini_set('include_path', ZEND_FRAMEWORK_PATH . (defined('INCLUDE_PATH') ? PATH_SEPARATOR . INCLUDE_PATH : ''));

ini_set('arg_separator.output',	  '&amp;');
ini_set('magic_quotes_runtime',	  0);

change_LoggingService::getInstance()->registerErrorHandler();

// Set the locale.
$localResult = setlocale(LC_ALL, 'en_US.UTF-8');

// Set GMT TimeZone
date_default_timezone_set('GMT');

if (Framework::inDevelopmentMode())
{
	change_InjectionService::getInstance()->update();
}
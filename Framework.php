<?php
umask(0002);
class Framework
{
	/**
	 * @param string $message
	 */
	public static function debug($message)
	{
		\Change\Application\LoggingManager::getInstance()->debug($message);
	}

	/**
	 * @param string $message
	 */
	public static function info($message)
	{
		\Change\Application\LoggingManager::getInstance()->info($message);
	}

	/**
	 * @param string $message
	 */
	public static function warn($message)
	{
		\Change\Application\LoggingManager::getInstance()->warn($message);
	}

	/**
	 * @param string $message
	 */
	public static function error($message)
	{
		\Change\Application\LoggingManager::getInstance()->error($message);
	}

	/**
	 * @param Exception $e
	 */
	public static function exception($e)
	{
		\Change\Application\LoggingManager::getInstance()->exception($e);
	}

	/**
	 * @param string $message
	 */
	public static function fatal($message)
	{
		\Change\Application\LoggingManager::getInstance()->fatal($message);
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
	 * @deprecated use \Change\Application\LoggingManager::getPriority()
	 */
	public static function isDebugEnabled()
	{
		return \Change\Application\LoggingManager::getInstance()->getPriority() >= \Zend\Log\Logger::DEBUG;
	}

	/**
	 * @deprecated use \Change\Application\LoggingManager::getPriority()
	 */
	public static function isInfoEnabled()
	{
		return \Change\Application\LoggingManager::getInstance()->getPriority() >= \Zend\Log\Logger::INFO;
	}
	
	/**
	 * @deprecated use \Change\Application\LoggingManager::getPriority()
	 */
	public static function isWarnEnabled()
	{
		return \Change\Application\LoggingManager::getInstance()->getPriority() >= \Zend\Log\Logger::WARN;
	}

	/**
	 * @deprecated use \Change\Application\LoggingManager::getPriority()
	 */
	public static function isErrorEnabled()
	{
		return \Change\Application\LoggingManager::getInstance()->getPriority() >= \Zend\Log\Logger::ERR;
	}

	/**
	 * @deprecated use \Change\Application\LoggingManager::getPriority()
	 */
	public static function isFatalEnabled()
	{
		return \Change\Application\LoggingManager::getInstance()->getPriority() >= \Zend\Log\Logger::EMERG;
	}
	
	/**
	 * @var \Zend\Log\Logger
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
	 * @return \Zend\Log\Logger
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
			
			self::$benchLog = new \Zend\Log\Logger();
			$filePath = f_util_FileUtils::buildLogPath('bench.log');
			if (!file_exists($filePath))
			{
				f_util_FileUtils::mkdir(dirname($filePath));
			}
			$writer = new \Zend\Log\Writer\Stream($filePath);
			self::$benchLog->addWriter($writer);
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
			$message = sprintf("%s\t%s\t%s\t%s\t%s\t%s", self::$benchSteps[self::$benchStepsIndex], $newStep, $newStep - self::$benchSteps[self::$benchStepsIndex], memory_get_usage(), $msg);
			$benchLog->notice($message);	
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
	 * @deprecated use \Change\Application::inDevelopmentMode()
	 */
	public static function inDevelopmentMode()
	{
		return \Change\Application::getInstance()->inDevelopmentMode();
	}
	
	/**
	 * @deprecated use \Change\Application::getProfile()
	 */
	public static function getProfile()
	{
		return \Change\Application::getInstance()->getProfile();
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
		return defined('DEFAULT_HOST') ? DEFAULT_HOST : $_SERVER['HTTP_HOST'];
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
	 * @deprecated use \Change\Application\Configuration::hasEntry()
	 */
	public static function hasConfiguration($path)
	{
		return \Change\Application::getInstance()->getConfiguration()->hasEntry($path);
	}

	/**
	 * @deprecated use \Change\Application\Configuration::getEntry()
	 */
	public static function getConfiguration($path, $strict = true)
	{
		return change_ConfigurationService::getInstance()->getConfiguration($path, $strict);
	}

	/**
	 * @deprecated use \Change\Application\Configuration::getEntry()
	 */
	public static function getConfigurationValue($path, $defaultValue = null)
	{
		return \Change\Application::getInstance()->getConfiguration()->getEntry($path, $defaultValue);
	}
	
	/**
	 * @return array
	 */
	public static function getHttpClientConfig()
	{
		return change_ConfigurationService::getInstance()->getConfiguration('http');
	}
	
	/**
	 * Registers namespace using Zend - this should always be called **after** registerAutoload
	 */
	public static function registerConfiguredAutoloads()
	{
		$application = \Change\Application::getInstance();
		$includePaths = $application->getConfiguration()->getEntry('autoload/paths', array());
		if (count($includePaths))
		{
			set_include_path(str_replace('{PROJECT_HOME}', PROJECT_HOME, implode(PATH_SEPARATOR, $includePaths)));
		}
		require_once PROJECT_HOME . '/Libraries/zendframework/zendframework/library/Zend/Loader/StandardAutoloader.php';
		$namespaces = $application->getConfiguration()->getEntry('autoload/namespaces', array());
		foreach ($namespaces as $namespace => $path)
		{
			$normalizedPath = str_replace('{PROJECT_HOME}', PROJECT_HOME, trim($path));
			$zendLoader  = new \Zend\Loader\StandardAutoloader();
			$zendLoader->registerNamespace($namespace, $normalizedPath);
			$zendLoader->register();
		}
	}
	
	/**
	 * Registers change's autoload
	 */
	public static function registerChangeAutoload()
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
	}
	
	public static function initialize()
	{
		require_once PROJECT_HOME . '/Change/Application.php';
		$application = \Change\Application::getInstance();
		$application->registerNamespaceAutoload();
		
		// Load configuration
		$application->loadConfiguration();
		if (!defined('FRAMEWORK_VERSION'))
		{
			define('FRAMEWORK_VERSION', CHANGE_VERSION);
		}
		
		self::registerChangeAutoload();				
		self::registerConfiguredAutoloads();
		
		$application->registerInjectionAutoload();
	
		if (self::inDevelopmentMode()) {error_reporting(E_ALL | E_STRICT);}
	
		ini_set('arg_separator.output', '&');
		ini_set('magic_quotes_runtime', 0);
	
		\Change\Application\LoggingManager::getInstance()->registerErrorHandler();
	
		// Set the locale.
		$localResult = setlocale(LC_ALL, 'en_US.UTF-8');
	
		// Set GMT TimeZone
		date_default_timezone_set('GMT');
	
		if (self::inDevelopmentMode())
		{
			\Change\Injection\Service::getInstance()->update();
		}
	}
	
	// Deprecated
	
	/**
	 * @deprecated
	 */
	public static function log($message, $priority)
	{
		\Change\Application\LoggingManager::getInstance()->fatal("Invalid call of Framework::log('$message', $priority).");
	}
}
<?php
umask(0002);
if (!defined('PROFILE') )
{
	$profile = file_get_contents(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'profile');
	if ( $profile === false || $profile == '' )
	{
		throw new Exception('Profile not defined. Please define a profile in file ./profile.');
	}
	define('PROFILE', trim($profile) );
}
if (!defined('FRAMEWORK_HOME'))
{
	define('FRAMEWORK_HOME', WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'framework');
}
if (!defined('AG_CACHE_DIR'))
{
	define('AG_CACHE_DIR', WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . PROFILE);
}

define('CHANGE_LOG_DIR', WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . PROFILE);
if (!is_dir(CHANGE_LOG_DIR)) @mkdir(CHANGE_LOG_DIR, 0777, true);
define('CHANGE_BUILD_DIR', WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . PROFILE);

class Framework
{
	/**
	 * @var integer
	 */
	static $logLevel = null;
	
	/**
	 * @var boolean
	 */
	private static $debugEnabled, $infoEnabled, $warnEnabled, $errorEnabled, $fatalEnabled;
	
	/**
	 * @var float[]
	 */
	private static $benchSteps = array();
	/**
	 * @var integer
	 */
	private static $benchStepsIndex = -1;

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
		return AG_DEVELOPMENT_MODE;
	}

	/**
	 * @return boolean
	 */
	public static function isSiteEnabled()
	{
		$siteDisabledFlag = f_util_FileUtils::buildWebeditPath("site_is_disabled");
		return !file_exists($siteDisabledFlag);
	}

	/**
	 * @param string $message
	 * @param integer $priority elementof {Logger::DEBUG, ...}
	 * @param string $loggerGroup
	 */
	public static function log($message, $priority, $loggerGroup = "webapp")
	{
		if (self::getLogLevel() <= $priority)
		{
			LoggingService::getInstance()->log($message, $loggerGroup);
		}
	}

	/**
	 * @param string $message
	 * @param string $loggerGroup
	 */
	public static function debug($message, $loggerGroup = "webapp")
	{
		self::log("[DEBUG]\t".$message, Logger::DEBUG, $loggerGroup);
	}

	/**
	 * @param string $message
	 * @param string $loggerGroup
	 */
	public static function info($message, $loggerGroup = "webapp")
	{
		self::log("[INFO]\t".$message, Logger::INFO, $loggerGroup);
	}

	/**
	 * @param string $message
	 * @param string $loggerGroup
	 */
	public static function warn($message, $loggerGroup = "webapp")
	{
		self::log("[WARN]\t".$message, Logger::WARN, $loggerGroup);
	}

	/**
	 * @param string $message
	 * @param string $loggerGroup
	 */
	public static function error($message, $loggerGroup = "webapp")
	{
		self::log("[ERROR]\t".$message, Logger::ERROR, $loggerGroup);
	}

	/**
	 * @param Exception $e
	 * @param string $loggerGroup
	 */
	public static function exception($e, $loggerGroup = "webapp")
	{
		self::log("[EXCEPTION]\t".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString(), Logger::ERROR, $loggerGroup);
	}

	/**
	 * @param string $message
	 * @param string $loggerGroup
	 */
	public static function fatal($message, $loggerGroup = "webapp")
	{
		self::log("[FATAL]\t".$message, Logger::FATAL, $loggerGroup);
	}

	/**
	 * @param string $message
	 */
	public static function deprecated($message)
	{
		if (self::inDevelopmentMode()) {trigger_error($message, E_USER_DEPRECATED);}
	}

	/**
	 * @return integer
	 */
	private static function getLogLevel()
	{
		if (self::$logLevel === null)
		{
			self::$logLevel = constant('Logger::'.AG_LOGGING_LEVEL);
			if (self::$logLevel == false) {self::$logLevel = Logger::WARN;}
		}
		return self::$logLevel;
	}

	/**
	 * @param integer $priority elementof {Logger::DEBUG, ...}
	 * @return boolean true if log of priority $priority is enabled
	 */
	private static function isLogEnabled($priority)
	{
		return self::getLogLevel() <= $priority;
	}

	/**
	 * @return boolean true if debug log is enabled
	 */
	public static function isDebugEnabled()
	{
		if (null === self::$debugEnabled)
		{
			self::$debugEnabled = self::isLogEnabled(Logger::DEBUG);
		}
		return self::$debugEnabled;
	}

	/**
	 * @return boolean true if info log is enabled
	 */
	public static function isInfoEnabled()
	{
		if (null === self::$infoEnabled)
		{
			self::$infoEnabled = self::isLogEnabled(Logger::INFO);
		}
		return self::$infoEnabled;
	}

	/**
	 * @return boolean true if warn log is enabled
	 */
	public static function isWarnEnabled()
	{
		if (null === self::$warnEnabled)
		{
			self::$warnEnabled = self::isLogEnabled(Logger::WARN);
		}
		return self::$warnEnabled;
	}

	/**
	 * @return boolean true if error log is enabled
	 */
	public static function isErrorEnabled()
	{
		if (null === self::$errorEnabled)
		{
			self::$errorEnabled = self::isLogEnabled(Logger::ERROR);
		}
		return self::$errorEnabled;
	}

	/**
	 * @return boolean true if fatal log is enabled
	 */
	public static function isFatalEnabled()
	{
		if (null === self::$fatalEnabled)
		{
			self::$fatalEnabled = self::isLogEnabled(Logger::FATAL);
		}
		return self::$fatalEnabled;
	}

	/**
	 * To benchmark a piece of code, use: 
	 * Framework::startBench(); // begin bench process
	 * $brands = $this->getBrandsList();
	 * Framework::bench("getBrandList"); // debug time between this call and last call
	 * foreach ($brands as $brand)
	 * {
	 *  $label = $brand->getLabel();
	 *  $index[strtolower($label[0])][$label] = $brand;
	 * }
	 * Framework::endBench("getBrandList processing"); // debug time between this call and last call and end bench process
	 */
	public static function startBench()
	{
		self::$benchSteps[] = microtime(true);
		self::$benchStepsIndex++;
	}

	/**
	 * @see Framework::startBench($msg)
	 * @param string $msg
	 */
	public static function bench($msg)
	{
		if (self::isDebugEnabled())
		{
			$newStep = microtime(true);
			self::debug('|BENCH|' . ($newStep-self::$benchSteps[self::$benchStepsIndex]) . '|' . $msg);
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
	public static function getBaseUrl()
	{
		$webSite = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
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
		if (!defined('AG_WEBAPP_HOST'))
		{

			$middlewareIni = Framework::getConfiguration('general');

			if ( isset($middlewareIni['server-fqdn']) )
			{
				define('AG_WEBAPP_HOST', $middlewareIni['server-fqdn']);
			}
			else
			{
				define('AG_WEBAPP_HOST', $_SERVER['HTTP_HOST']);
			}

		}
		return AG_WEBAPP_HOST;
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
	 * Return true if the $path configuration exist
	 * @param string $path        	
	 */
	public static function hasConfiguration($path)
	{
		return change_ConfigurationService::getInstance()->hasConfiguration($path);
	}

	/**
	 * Return an array with part of configuration of Framework
	 * or throw a Exception if the $path configuration does not exist
	 *
	 * @param string $path        	
	 * @param boolean $strict        	
	 * @throws Exception if the $path configuration does not exist
	 * @return string|false if the path was not founded and strict value if  false
	 */
	public static function getConfiguration($path, $strict = true)
	{
		return change_ConfigurationService::getInstance()->getConfiguration($path, $strict);
	}

	/**
	 * Return an array with part of configuration of Framework
	 * or null if the $path configuration does not exist
	 *
	 * @param string $path        	
	 * @param mixed $defaultValue        	
	 * @return mixed|null
	 */
	public static function getConfigurationValue($path, $defaultValue = null)
	{
		return change_ConfigurationService::getInstance()->getConfigurationValue($path, $defaultValue);
	}
	
	//DEPRECATED

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
	public static function loadConfiguration($env = '', $onlyConfig = false)
	{
		change_ConfigurationService::getInstance()->loadConfiguration($env, $onlyConfig);
	}

	/**
	 * @deprecated use change_ConfigurationService::loadConfiguration()
	 */
	public static function reloadConfiguration($env = '')
	{
		change_ConfigurationService::getInstance()->loadConfiguration($env);
	}
	
	/**
	 * @deprecated
	 */
	public static function getCompanyName()
	{
		return AG_WEBAPP_NAME;
	}
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	public final static function parseComponentType($componentType,$whatwewant=null,$noException=false)
	{
		$matches = array();
		if (preg_match('#^(.*)/([\w_\-]+)$#', $componentType, $matches))
		{
			$res = array();
			$res['component'] = $matches[2];
			$res['package']   = $matches[1];
			$tt = explode("_", $res['package']);

			if ($tt[0] == "framework")
			{
				$res['package_type'] = "framework";
				$res['package_name'] = "framework";
			}
			else
			{
				$res['package_type'] = $tt[0];
				$res['package_name'] = $tt[1];
			}

			if ($whatwewant===null)
			{
				return $res;
			}
			else
			{
				return $res[$whatwewant];
			}
		}

		if ($noException) return null;

		$e = new ClassException("invalid-component-type");
		$e->setAttribute('componentType', $componentType);
		throw $e;
	}
}

// Load configuration
require_once(FRAMEWORK_HOME . '/service/ConfigurationService.class.php');
change_ConfigurationService::getInstance()->loadConfiguration(PROFILE);

require_once(FRAMEWORK_HOME . '/loader/ResourceResolver.class.php');
require_once(FRAMEWORK_HOME . '/loader/ClassResolver.class.php');
require_once(FRAMEWORK_HOME . '/loader/Resolver.class.php');
require_once(FRAMEWORK_HOME . '/loader/ResourceLoader.class.php');
require_once(FRAMEWORK_HOME . '/loader/ClassLoader.class.php');
require_once(FRAMEWORK_HOME . '/loader/Loader.class.php');


if (spl_autoload_register(array(ClassLoader::getInstance(), "autoload")) === false)
{
	throw new Exception("Could not register Change framework autoload function");
}

define('AG_WEBAPP_DIR', PROJECT_OVERRIDE);

ini_set('include_path', FRAMEWORK_HOME . '/libs/pear' . PATH_SEPARATOR . PEAR_DIR);

ini_set('arg_separator.output',      '&amp;');
ini_set('magic_quotes_runtime',      0);

require_once(FRAMEWORK_HOME.'/libs/mvc/Context.class.php');
require_once(FRAMEWORK_HOME.'/libs/mvc/Controller.class.php');
require_once(FRAMEWORK_HOME.'/libs/mvc/Request.class.php');
require_once(FRAMEWORK_HOME.'/libs/mvc/Storage.class.php');
require_once(FRAMEWORK_HOME.'/libs/mvc/User.class.php');
require_once(FRAMEWORK_HOME.'/libs/mvc/Action.class.php');
require_once(FRAMEWORK_HOME.'/libs/mvc/View.class.php');

// Load modules informations
require_once(FRAMEWORK_HOME."/service/Injection.php");
require_once(FRAMEWORK_HOME."/service/BaseService.class.php");
require_once(FRAMEWORK_HOME."/service/ModuleService.class.php");
$ms = ModuleService::getInstance();
$ms->loadCacheFile();

require_once(FRAMEWORK_HOME . '/service/LoggingService.class.php');
LoggingService::getInstance()->registerErrorHandler();

// Set the locale.
$localResult = setlocale(LC_ALL, 'en_US.UTF-8');

// Set GMT TimeZone
date_default_timezone_set('GMT');

<?php
umask(0002);
if (!defined('PROFILE') )
{
	$profile = file_get_contents(PROJECT_HOME . DIRECTORY_SEPARATOR . 'profile');
	if ( $profile === false || $profile == '' )
	{
		throw new Exception('Profile not defined. Please define a profile in file ./profile.');
	}
	define('PROFILE', trim($profile) );
}

define('CHANGE_LOG_DIR', PROJECT_HOME . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . PROFILE);

if (!is_dir(CHANGE_LOG_DIR)) @mkdir(CHANGE_LOG_DIR, 0777, true);

define('CHANGE_BUILD_DIR', PROJECT_HOME . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . PROFILE);

class Framework
{
	/**
	 * The project config compiled
	 */
	private static $config = null;
	static $logLevel = null;
	private static $debugEnabled, $infoEnabled, $warnEnabled, $errorEnabled, $fatalEnabled;

	/**
	 * @var Float[]
	 */
	private static $benchSteps = array();
	/**
	 * @var Integer
	 */
	private static $benchStepsIndex = -1;

	/**
	 * @return String
	 */
	public static function getVersion()
	{
		return "4.0.0";
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
		return PROFILE;
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
	 * @param String $message
	 * @see Logger
	 * @param Integer $priority elementof {Logger::DEBUG, ...}
	 * @param String $loggerGroup
	 */
	public static function log($message, $priority, $loggerGroup = "webapp")
	{
		if (self::getLogLevel() <= $priority)
		{
			LoggingService::getInstance()->log($message, $loggerGroup);
		}
	}

	public static function debug($message, $loggerGroup = "webapp")
	{
		self::log("[DEBUG]\t".$message, Logger::DEBUG, $loggerGroup);
	}

	public static function info($message, $loggerGroup = "webapp")
	{
		self::log("[INFO]\t".$message, Logger::INFO, $loggerGroup);
	}

	public static function warn($message, $loggerGroup = "webapp")
	{
		self::log("[WARN]\t".$message, Logger::WARN, $loggerGroup);
	}

	public static function error($message, $loggerGroup = "webapp")
	{
		self::log("[ERROR]\t".$message, Logger::ERROR, $loggerGroup);
	}

	public static function exception($e, $loggerGroup = "webapp")
	{
		self::log("[EXCEPTION]\t".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString(), Logger::ERROR, $loggerGroup);
	}

	public static function fatal($message, $loggerGroup = "webapp")
	{
		self::log("[FATAL]\t".$message, Logger::FATAL, $loggerGroup);
	}

	private static function getLogLevel()
	{
		if (self::$logLevel === null)
		{
			self::$logLevel = constant('Logger::'.LOGGING_LEVEL);
			if (self::$logLevel == false) {self::$logLevel = Logger::WARN;}
		}
		return self::$logLevel;
	}
	
	/**
	 * @return string (DEBUG, INFO, WARN, ERROR, FATAL) 
	 */
	public static function getLogLevelName()
	{
		return LOGGING_LEVEL;
	}

	/**
	 * @see Logger
	 * @param Integer $priority elementof {Logger::DEBUG, ...}
	 * @return Boolean true if log of priority $priority is enabled
	 */
	private static function isLogEnabled($priority)
	{
		return self::getLogLevel() <= $priority;
	}

	/**
	 * @return Boolean true if debug log is enabled
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
	 * @return Boolean true if info log is enabled
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
	 * @return Boolean true if warn log is enabled
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
	 * @return Boolean true if error log is enabled
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
	 * @return Boolean true if fatal log is enabled
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
	 * @example
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
	public static function startBench()
	{
		self::$benchSteps[] = microtime(true);
		self::$benchStepsIndex++;
	}

	/**
	 * @see Framework::startBench($msg)
	 * @param String $msg
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
	 * Return an array with configuration of Framework
	 */
	public static function getAllConfiguration()
	{
		return self::$config;
	}


	/**
	 * Return true if the $path configuration exist
	 * @param String $path
	 * @example Framework::hasConfiguration('databases')
	 * @example Framework::hasConfiguration('databases/webapp')
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
	 * @throws ConfigurationException if the $path configuration does not exist
	 * @example Framework::getConfiguration('databases')
	 * @example Framework::getConfiguration('databases/webapp')
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
	 *
	 * @param string $env
	 * @param Boolean $onlyConfig
	 */
	public static function loadConfiguration($currentProfile = '', $onlyConfig = false)
	{
		// If configuration not yet loaded, load it
		if (self::$config === null)
		{
			$cacheConfigDir = PROJECT_HOME . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'config';	
			
			if (!$onlyConfig)
			{
				$cacheDefinesFile = $cacheConfigDir."/project.".$currentProfile.".defines.php";
				if (!is_file($cacheDefinesFile))
				{
					throw new Exception("Could not find $cacheDefinesFile. You must compile your configuration.");
				}
				require($cacheDefinesFile);
			}
			
			$cacheFile = $cacheConfigDir."/project.".$currentProfile.".php";
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
	public static function reloadConfiguration($env = '')
	{
		if (self::$config !== null)
		{
			self::$config = null;
			self::loadConfiguration($env, true);
			// TODO: inverse dependences
			ModuleService::clearInstance();
			generator_PersistentModel::reloadModels();
		}
	}

	/**
	 * @return String
	 */
	public static function getCompanyName()
	{
		return self::$config['general']['projectName'];
	}
}

// Load configuration
Framework::loadConfiguration(PROFILE);

require_once(PROJECT_HOME . '/framework/loader/ResourceResolver.class.php');
require_once(PROJECT_HOME . '/framework/loader/ClassResolver.class.php');
require_once(PROJECT_HOME . '/framework/loader/Resolver.class.php');
require_once(PROJECT_HOME . '/framework/loader/ResourceLoader.class.php');
require_once(PROJECT_HOME . '/framework/loader/ClassLoader.class.php');
require_once(PROJECT_HOME . '/framework/loader/Loader.class.php');

function f_errorHandler($errno, $errstr, $errfile, $errline)
{
	$message = "(type $errno,line $errline) $errstr in file ($errfile)";
	switch ($errno)
	{
		case E_USER_ERROR:
		case E_USER_WARNING:
			LoggingService::getInstance()->errorLog($message, 'error');
			throw new Exception($message);
			break;
		case E_STRICT:
			//do nothing
			break;
		case E_USER_NOTICE:
		default:
			LoggingService::getInstance()->errorLog($message, 'error');
			break;
	}
}

if (spl_autoload_register(array(ClassLoader::getInstance(), "autoload")) === false)
{
	throw new Exception("Could not register Change framework autoload function");
}


ini_set('include_path', PEAR_DIR);

ini_set('arg_separator.output',      '&amp;');
ini_set('display_errors',            1);
ini_set('magic_quotes_runtime',      0);

error_reporting(E_ALL);

// Load modules informations
require_once(PROJECT_HOME . '/framework/service/Injection.php');
require_once(PROJECT_HOME . '/framework/service/BaseService.class.php');
require_once(PROJECT_HOME . '/framework/service/ModuleService.class.php');
$ms = ModuleService::getInstance();
$ms->loadCacheFile();

require_once(PROJECT_HOME . '/framework/service/LoggingService.class.php');

// +---------------------------------------------------------------------------+
// | error handler
// +---------------------------------------------------------------------------+
// Configuration du gestionnaire d'erreurs
set_error_handler("f_errorHandler");

// Set the locale.
$localResult = setlocale(LC_ALL, 'en_US.UTF-8');

// Set GMT TimeZone
date_default_timezone_set('GMT');
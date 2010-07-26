<?php
umask(0002);

if ( ! defined('PROFILE') )
{
	$profile = file_get_contents(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'profile');
	if ( $profile === false || $profile == '' )
	{
		throw new Exception('Profile not defined. Please define a profile in file ./profile.');
	}
	define('PROFILE', trim($profile) );
}

define('FRAMEWORK_HOME', WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'framework');
define('AG_CACHE_DIR', WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . PROFILE);
define('CHANGE_LOG_DIR', WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . PROFILE);
if (!is_dir(CHANGE_LOG_DIR)) @mkdir(CHANGE_LOG_DIR, 0777, true);
define('CHANGE_BUILD_DIR', WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . PROFILE);

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
		return "3.0.3";
	}

	/**
	 * @see project config and AG_DEVELOPMENT_MODE constant
	 * @return Boolean
	 */
	public static function inDevelopmentMode()
	{
		return AG_DEVELOPMENT_MODE;
	}

	/**
	 * @return Boolean
	 */
	public static function isSiteEnabled()
	{
		$siteDisabledFlag = f_util_FileUtils::buildWebeditPath("site_is_disabled");
		return !file_exists($siteDisabledFlag);
	}

	/**
	 * Log a message using $priority as log level.
	 * Be aware to use isLogEnabled() if $message is "heavy".<br/>
	 * Example :
	 * <code>
	 * if (Framework::isLogEnabled(Logger::DEBUG))
	 * {
	 *     Framework::log("This is a 'heavy' message because there are "
	 *                    . "some string concatenations and or method calls : "
	 *                    . $anObject->anMethod.", Logger::DEBUG);
	 * }
	 * </code>
	 *
	 * @param String $message
	 * @see Logger
	 * @param Integer $priority elementof {Logger::DEBUG, ...}
	 * @param String $loggerGroup
	 */
	public static function log($message, $priority, $loggerGroup = "webapp")
	{
		if (AG_USE_LOGGING && self::getLogLevel() <= $priority)
		{
			//gmdate('Y-m-d H:i:s').' '.$message
			LoggerManager::groupLog(new Message(gmdate('Y-m-d H:i:s').' '.$message, $priority), $loggerGroup);
		}
		// FIXME : what if !AG_USE_LOGGING ?
	}

	public static function debug($message, $loggerGroup = "webapp")
	{
		self::log('[DEBUG] '.$message, Logger::DEBUG, $loggerGroup);
	}

	public static function info($message, $loggerGroup = "webapp")
	{
		self::log('[INFO] '.$message, Logger::INFO, $loggerGroup);
	}

	public static function warn($message, $loggerGroup = "webapp")
	{
		self::log('[WARN] '.$message, Logger::WARN, $loggerGroup);
	}

	public static function error($message, $loggerGroup = "webapp")
	{
		self::log('[ERROR] '.$message, Logger::ERROR, $loggerGroup);
	}

	public static function exception($e, $loggerGroup = "webapp")
	{
		self::log('[EXCEPTION] '.get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString(), Logger::ERROR, $loggerGroup);
	}

	public static function fatal($message, $loggerGroup = "webapp")
	{
		self::log('[FATAL] '.$message, Logger::FATAL, $loggerGroup);
	}

	private static function getLogLevel()
	{
		if (is_null(self::$logLevel))
		{
			self::$logLevel = constant('Logger::'.AG_LOGGING_LEVEL);
			if (self::$logLevel == false) throw new FrameworkException('invalid-AG_LOGGING_LEVEL');
		}
		return self::$logLevel;
	}

	/**
	 * @see Logger
	 * @param Integer $priority elementof {Logger::DEBUG, ...}
	 * @return Boolean true if log of priority $priority is enabled
	 */
	private static function isLogEnabled($priority)
	{
		return AG_USE_LOGGING && self::getLogLevel() <= $priority;
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
	 * @deprecated 1.1 - 26 avr. 2006
	 * @see ComponentTypeObject
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
		return $webSite->getProtocol() . '://'. $webSite->getDomain();
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
	 * or throw a ConfigurationException if the $path configuration does not exist
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
					throw new ConfigurationException('Part of configuration ' . $part . ' not found.');
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
	 * @param string $version '2.0.0'
	 */
	public static function addPackageConfiguration($packageName, $version)
	{
		if (isset(self::$config) && isset(self::$config['packageversion']))
		{
			self::$config['packageversion'][$packageName] = $version;
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
	public static function loadConfiguration($env = '', $onlyConfig = false)
	{
		// If configuration not yet loaded, load it
		if (self::$config === null)
		{
			// If specific environnement add a dot to complet in path file
			if ( $env != '' )
			{
				$env .= '.';
			}

			$fileName = 'project.'.$env.'xml.php';
			$pathOfConfigFile = WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $fileName;
			self::$config = array();
			if (!is_file($pathOfConfigFile))
			{
				throw new Exception("Could not find $pathOfConfigFile. You must compile your configuration.");
			}
			if (!$onlyConfig)
			{
				require($pathOfConfigFile);
			}
			else
			{
				$config = file($pathOfConfigFile);
				if ($config === false)
				{
					throw new Exception("Could not read $pathOfConfigFile");
				}
				unset($config[0]); // first line is php tag
				foreach ($config as $key => $value)
				{
					if (preg_match('/^define\(\'([^\']+)\', .*$/', $value, $matches))
					{
						 $constantName = $matches[1];
						 if (defined($constantName)) // ignore define as we can not redeclare a constant
						 {
						 	unset($config[$key]);
						 }
					}
				}
				eval(join("\n", $config));
			}
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
		return AG_WEBAPP_NAME;
	}
}

// Load configuration
Framework::loadConfiguration(PROFILE);

require_once(FRAMEWORK_HOME . '/loader/ResourceResolver.class.php');
require_once(FRAMEWORK_HOME . '/loader/ClassResolver.class.php');
require_once(FRAMEWORK_HOME . '/loader/Resolver.class.php');
require_once(FRAMEWORK_HOME . '/loader/ResourceLoader.class.php');
require_once(FRAMEWORK_HOME . '/loader/ClassLoader.class.php');
require_once(FRAMEWORK_HOME . '/loader/Loader.class.php');
if (AG_DEVELOPMENT_MODE)
{
	require_once(FRAMEWORK_HOME."/aop/AOP.php");
}

function f_errorHandler($errno, $errstr, $errfile, $errline)
{
	$message = gmdate('Y-m-d H:i:s')." (type $errno,line $errline) $errstr in file ($errfile)\n";

	switch ($errno)
	{
		case E_USER_ERROR:
		case E_USER_WARNING:
			$filepath = CHANGE_LOG_DIR . DIRECTORY_SEPARATOR . 'phpfatal.log';
			@file_put_contents($filepath,$message,FILE_APPEND);
			throw new Exception($message);
			break;
		case E_STRICT:
			//do nothing
			break;
		case E_USER_NOTICE:
		default:
			$filepath = CHANGE_LOG_DIR . DIRECTORY_SEPARATOR . 'phperror.log';
			@file_put_contents($filepath,$message,FILE_APPEND);

			break;
	}
}

if (spl_autoload_register(array(ClassLoader::getInstance(), "autoload")) === false)
{
	throw new Exception("Could not register Change framework autoload function");
}

// +---------------------------------------------------------------------------+
// | Should we run the system in debug mode? When this is on, there may be     |
// | various side-effects. But for the time being it only deletes the cache    |
// | upon start-up.                                                            |
// |                                                                           |
// | This should stay on while you're developing your application, because     |
// | many errors can stem from the fact that you're using an old cache file.   |
// +---------------------------------------------------------------------------+
define('AG_DEBUG', MODE_DEBUG);

// +---------------------------------------------------------------------------+
// | An absolute filesystem path to your web application directory. This       |
// | directory is the root of your web application, which includes the core    |
// | configuration files and related web application data.                     |
// +---------------------------------------------------------------------------+
define('AG_WEBAPP_DIR', PROJECT_OVERRIDE);

// +---------------------------------------------------------------------------+
// | error handler
// +---------------------------------------------------------------------------+
// Configuration du gestionnaire d'erreurs
set_error_handler("f_errorHandler");

ini_set('include_path', FRAMEWORK_HOME . '/libs/agavi'
. PATH_SEPARATOR . WEBEDIT_HOME.'/libs/agavi'
. PATH_SEPARATOR . FRAMEWORK_HOME . '/libs/pear'
. PATH_SEPARATOR . PEAR_DIR);

ini_set('arg_separator.output',      '&amp;');
ini_set('display_errors',            1);
ini_set('magic_quotes_runtime',      0);
ini_set('unserialize_callback_func', 'f_autoload');

error_reporting(E_ALL);

require_once(WEBEDIT_HOME.'/libs/agavi/core/AgaviObject.class.php');
require_once(WEBEDIT_HOME.'/libs/agavi/util/ParameterHolder.class.php');
require_once(FRAMEWORK_HOME.'/libs/agavi/config/ConfigHandler.class.php');
require_once(WEBEDIT_HOME.'/libs/agavi/util/Toolkit.class.php');

// Load modules informations
require_once(FRAMEWORK_HOME."/service/Injection.php");
require_once(FRAMEWORK_HOME."/service/BaseService.class.php");
require_once(FRAMEWORK_HOME."/service/ModuleService.class.php");
$ms = ModuleService::getInstance();
$ms->loadCacheFile();

// Load logging config
if (AG_USE_LOGGING)
{
	// TODO: remove agavi (...).
	require_once(FRAMEWORK_HOME."/libs/agavi/config/ConfigCache.class.php");
	require_once(WEBEDIT_HOME.'/libs/agavi/config/IniConfigHandler.class.php');
	require_once(WEBEDIT_HOME.'/libs/agavi/config/RootConfigHandler.class.php');
	require_once(WEBEDIT_HOME.'/libs/agavi/config/FactoryConfigHandler.class.php');
	require_once(WEBEDIT_HOME.'/libs/agavi/config/FilterConfigHandler.class.php');
	require_once(WEBEDIT_HOME.'/libs/agavi/config/ValidatorConfigHandler.class.php');
	require_once(WEBEDIT_HOME.'/libs/agavi/config/ParameterParser.class.php');
	require_once(FRAMEWORK_HOME."/libs/agavi/config/ChangeLoggingConfigHandler.class.php");
	require_once(FRAMEWORK_HOME."/libs/agavi/config/ConfigCache.class.php");
	require_once(WEBEDIT_HOME."/libs/agavi/logging/Layout.class.php");
	require_once(FRAMEWORK_HOME."/libs/agavi/logging/PassthruLayout.class.php");
	require_once(WEBEDIT_HOME."/libs/agavi/logging/Appender.class.php");
	require_once(FRAMEWORK_HOME."/libs/agavi/logging/FileAppender.class.php");
	require_once(FRAMEWORK_HOME."/libs/agavi/logging/LoggingFileAppender.class.php");
	require_once(FRAMEWORK_HOME."/libs/agavi/logging/Logger.class.php");
	require_once(FRAMEWORK_HOME."/libs/agavi/logging/LoggerManager.class.php");
	
	ConfigCache::import(AG_CONFIG_DIR . '/logging.ini');
}

// Set the locale.
$localResult = setlocale(LC_ALL, 'en_US.UTF-8');

// Set GMT TimeZone
date_default_timezone_set('GMT');

<?php
/**
 * @package framework.object
 */
class RequestContext
{
	/**
	 * @var RequestContext
	 */
	private static $m_instance;

	/**
	 * @var array
	 */
	private $m_workLang = array();

	/**
	 * @var string (lowercase) Ex: fr
	 */
	private $m_lang;

	/**
	 * @var boolean
	 */
	private $m_isLangDefined = false;

	/**
	 * Language used for the Backoffice UI (could be different from the above one).
	 *
	 * @var string (lowercase) Ex: fr
	 */
	private $m_ui_lang;

	/**
	 * @var boolean
	 */
	private $m_enabled = false;

	/**
	 * @var array
	 */
	private $m_supportedLanguages = array();

	/**
	 * Languages supported by the Backoffice UI (could be different from the above ones).
	 *
	 * @var array
	 */
	private $m_ui_supportedLanguages = array();
	
	
	private $m_i18n_synchro = null;

	protected function loadI18nSynchroConfiguration()
	{
		$this->m_i18n_synchro = false;
		$data = Framework::getConfigurationValue('i18nsynchro', null);
	
		if (is_array($data) && count($data))
		{
			$langs = RequestContext::getInstance()->getSupportedLanguages();
			$result = array();
			foreach ($data as $lang => $froms)
			{
				if (in_array($lang, $langs))
				{
					$fromLangs = array();
					foreach (array_map('trim', explode(',', $froms)) as $fromLang)
					{
						if (in_array($fromLang, $langs))
						{
							$fromLangs[] = $fromLang;
						}
					}
						
					if (count($fromLangs))
					{
						$result[$lang] = $fromLangs;
					}
				}
			}
				
			if (count($result))
			{
				$this->m_i18n_synchro = $result;
			}
		}
	}
	
	/**
	 * @return boolean
	 */
	public function hasI18nSynchro()
	{
		if ($this->m_i18n_synchro === null)
		{
			$this->loadI18nSynchroConfiguration();
		}
		return $this->m_i18n_synchro !== false;
	}
	
	/**
	 * @return array string : string[]
	 */
	public function getI18nSynchro()
	{
		return $this->hasI18nSynchro() ? $this->m_i18n_synchro : array();
	}	
	
	/**
	 * @var string
	 */
	private $m_timeZone;

	/**
	 * @var boolean
	 */
	private $m_inHTTPS;

	/**
	 * @var string
	 */
	private $m_pathURI;
	
	

	/**
	 * The possible request modes : backoffice or frontoffice
	 */
	const BACKOFFICE_MODE = 0;
	const FRONTOFFICE_MODE = 1;

	/**
	 * Enter description here...
	 *
	 * @var Integer FRONTOFFICE_MODE or BACKOFFICE_MODE
	 */
	private $mode;

	/**
	 * Constructor of RequestContext
	 * @param array $supportedLanguages
	 * @param array $ui_supportedLanguages
	 */
	protected function __construct($supportedLanguages, $ui_supportedLanguages)
	{
		$this->m_supportedLanguages = $supportedLanguages;
		$this->m_enabled = (count($this->m_supportedLanguages) > 1);
		$this->m_lang = $this->getDefaultLang();
		$this->m_ui_supportedLanguages = $ui_supportedLanguages;
		$this->m_ui_lang = isset($_SESSION['uilang']) ? $_SESSION['uilang'] : $this->getUserAgentLanguage();
		// This "marker" can be overriden especially when behind proxies
		$httpsMarker = Framework::getConfigurationValue('general/https-request-marker', 'HTTPS');
		$httpsMarkerValue = Framework::getConfigurationValue('general/https-request-marker-value', 'on');
		$this->m_inHTTPS = (isset($_SERVER[$httpsMarker]) && ($_SERVER[$httpsMarker] === $httpsMarkerValue));
		$this->m_pathURI = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : null;

		if (Framework::isDebugEnabled())
		{
			$debug = __METHOD__ . ' -> default lang : ' . var_export($this->m_lang, true);
			$debug .= ', default UI lang : ' .var_export($this->m_ui_lang, true);
			$debug .= ', HTTPS : ' . var_export($this->m_inHTTPS, true);
			$debug .= ', URL : ' . var_export($this->m_pathURI, true);
			Framework::debug($debug);
		}
	}

	/**
	 * @param string $supportedLanguages
	 * @param string $ui_supportedLanguages
	 * @return RequestContext
	 */
	public static function getInstance($supportedLanguages = null, $ui_supportedLanguages = null)
	{
		if (self::$m_instance === null)
		{
			if ($supportedLanguages !== null)
			{
				$languages = explode(' ', strtolower($supportedLanguages));
			}
			else if (defined('AG_SUPPORTED_LANGUAGES'))
			{
				$languages = explode(' ', strtolower(AG_SUPPORTED_LANGUAGES));
			}
			else
			{
				$languages = array(K::DEFAULT_LANG);
			}

			if ($ui_supportedLanguages !== null)
			{
				$ui_languages = explode(' ', strtolower($ui_supportedLanguages));
			}
			else if (defined('AG_UI_SUPPORTED_LANGUAGES'))
			{
				$ui_languages = explode(' ', strtolower(AG_UI_SUPPORTED_LANGUAGES));
			}
			else
			{
				$ui_languages = array(K::DEFAULT_LANG);
			}

			self::$m_instance = new RequestContext($languages, $ui_languages);
		}

		return self::$m_instance;
	}

	public static function clearInstance()
	{
		self::$m_instance = null;
	}

	/**
	 * @return boolean
	 */
	public function inHTTPS()
	{
		return $this->m_inHTTPS;
	}

	/**
	 * @return String (http | https)
	 */
	public function getProtocol()
	{
		if ($this->m_inHTTPS)
		{
			return 'https';
		}
		return 'http';
	}


	public function getPathURI()
	{
		return $this->m_pathURI;
	}

	/**
	 * The current request mode
	 * @return const FRONTOFFICE_MODE or BACKOFFICE_MODE
	 */
	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * Set the current request mode
	 * @param const $mode FRONTOFFICE_MODE or BACKOFFICE_MODE
	 */
	public function setMode($mode)
	{
		$this->mode = $mode;
	}

	/**
	 * @var Boolean
	 */
	private $ajaxMode;
	/**
	 * @var String
	 */
	private $ajaxFromURL;

	/**
	 * @param Boolean $ajaxMode
	 * @param String $fromURL
	 * @return void
	 */
	public function setAjaxMode($ajaxMode, $fromURL)
	{
		$this->ajaxMode = $ajaxMode;
		$this->ajaxFromURL = $fromURL;
	}

	/**
	 * @return Boolean
	 */
	public function getAjaxMode()
	{
		return $this->ajaxMode;
	}

	/**
	 * @return String
	 */
	public function getAjaxFromURL()
	{
		return $this->ajaxFromURL;
	}

	/**
	 * @return String
	 */
	public function getAjaxFromURI()
	{
		$matches = array();
		if (preg_match('/^http(?:s){0,1}:\/\/([^\/]+)(\/.*)$/', $this->ajaxFromURL, $matches))
		{
			return $matches[2];
		}
		throw new Exception("Could not parse ".$this->ajaxFromURL);
	}

	/**
	 * @return boolean
	 */
	public function isMultiLangEnabled()
	{
		return $this->m_enabled;
	}

	/**
	 * @return boolean
	 */
	public function isLangDefined()
	{
		return $this->m_isLangDefined;
	}

	/**
	 * @return string current language to lower case Ex: fr
	 */
	public function getLang()
	{
		if (count($this->m_workLang) > 0)
		{
			$lang = end($this->m_workLang);
		}
		else
		{
			$lang = $this->m_lang;
		}
		return $lang;
	}

	/**
	 * @exception BadInitializationException if the current UI language is not defined
	 * @return string current UI language to lower case Ex: fr
	 */
	public function getUILang()
	{
		return $this->m_ui_lang;
	}

	/**
	 * @exception BadInitializationException if the lang is already defined to other language
	 * @exception IllegalArgumentException if the lang is not supported
	 * @param string $lang
	 */
	public function setLang($lang)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . '(' . $lang . ')');
		}
	  
		if (count($this->m_workLang) > 0)
		{
			throw new BadInitializationException("The current language is already defined to :" . $this->m_lang);
		}
	  
		if (in_array($lang, $this->getSupportedLanguages()))
		{
			$this->m_lang = $lang;
			$this->m_isLangDefined = true;
		}
	}

	/**
	 * @exception IllegalArgumentException if the UI lang is not supported
	 * @param string $lang
	 */
	public function setUILang($lang)
	{
		if (in_array($lang, $this->getUISupportedLanguages()))
		{
			$this->m_ui_lang = $lang;
		}
		else
		{
			throw new IllegalArgumentException($lang);
		}
	}


	public function setUILangFromParameter($lang)
	{

		if ($lang && is_string($lang) && in_array($lang, $this->getUISupportedLanguages()))
		{
			$this->m_ui_lang = $lang;
		}
	}


	/**
	 * @return array
	 */
	public function getSupportedLanguages()
	{
		return $this->m_supportedLanguages;
	}

	/**
	 * @return array
	 */
	public function getUISupportedLanguages()
	{
		return $this->m_ui_supportedLanguages;
	}

	/**
	 * @return string
	 */
	public function getDefaultLang()
	{
		return $this->m_supportedLanguages[0];
	}

	/**
	 * @return string
	 */
	public function getUIDefaultLang()
	{
		return $this->m_ui_supportedLanguages[0];
	}

	/**
	 * Retrieve available UA language.
	 *
	 * @return string
	 */
	public function getUserAgentLanguage()
	{
		$lang = null;

		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			$lang = preg_split('/[,;]+/', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$lang = strtolower(substr(trim($lang[0]), 0, 2));
		}

		if ($lang === null || !in_array($lang, $this->getUISupportedLanguages()))
		{
			$lang = $this->getUIDefaultLang();
		}

		return $lang;
	}

	public function beginI18nWork($lang)
	{
		if ($lang === null || !in_array($lang, $this->m_supportedLanguages))
		{
			throw new IllegalArgumentException($lang);
		}
		if (Framework::isDebugEnabled())
		{
			Framework::debug("RequestContext->beginI18nWork($lang) - " .count($this->m_workLang));
		}
		array_push($this->m_workLang, $lang);
	}

	/**
	 * @param Exception $exception
	 * @throws $exception if provided
	 */
	public function endI18nWork($exception = null)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("RequestContext->endI18nWork() - " .count($this->m_workLang));
		}

		array_pop($this->m_workLang);
		if ($exception !== null)
		{
			throw $exception;
		}
	}

	/**
	 * @return String
	 */
	public function getTimeZone()
	{
		if (NULL === $this->m_timeZone)
		{
			$this->m_timeZone = DEFAULT_TIMEZONE;
		}

		return $this->m_timeZone;
	}

	/**
	 * @var Object
	 */
	private $m_browser;

	private $m_os;

	private $m_userAgentType;

	private $m_userAgentTypeVersion;

	private $m_mimeContentType;

	/**
	 * @return Object
	 */
	private function getBrowser()
	{
		if (is_null($this->m_browser))
		{
			if (isset($_SERVER['HTTP_USER_AGENT']))
			{
				$userAgent = $_SERVER['HTTP_USER_AGENT'];
			}
			else
			{
				$userAgent = "";
			}
			$browscap = new Browscap(AG_CACHE_DIR);
			$this->m_browser = $browscap->getBrowser($userAgent);
			if (Framework::isDebugEnabled())
			{
				Framework::debug("GraphicalEngine : " . $this->m_browser->GraphicalEngine . ", ".$this->m_browser->GraphicalEngineVer);
			}
		}
		return $this->m_browser;
	}

	const OS_WIN = "windows";
	const OS_MAC = "macos";
	const OS_LINUX = "linux";
	const OS_OTHER = "other";

	/**
	 * Get the current operating system
	 * @return String ("windows", "macos", "linux", "other")
	 */
	public function getOperatingSystem()
	{
		if (is_null($this->m_os))
		{
			$this->m_os = self::OS_OTHER;

			if (Controller::getInstance()->getContext()->getRequest()->getMethod() == Request::CONSOLE)
			{
				$os = strtolower(PHP_OS);

				if ($os == 'linux')
				{
					$this->m_os = self::OS_LINUX;
				}
				else if ($os == 'darwin')
				{
					$this->m_os = self::OS_MAC;
				}
				else if (strpos($os, 'win') !== false)
				{
					$this->m_os = self::OS_WIN;
				}
			}
			else if (isset($_SERVER['HTTP_USER_AGENT']))
			{
				$ua = strtolower($_SERVER['HTTP_USER_AGENT']);

				if (strpos($ua, 'windows') !== false)
				{
					$this->m_os = self::OS_WIN;
				}
				else if(strpos($ua, 'macintosh') !== false)
				{
					$this->m_os = self::OS_MAC;
				}
				else if(strpos($ua, 'linux') !== false)
				{
					$this->m_os = self::OS_LINUX;
				}
			}
		}

		return $this->m_os;
	}

	/**
	 * Get the current graphical engine type
	 * @return String (all, gecko, trident, presto, khtml ...)
	 */
	public function getUserAgentType()
	{
		if (is_null($this->m_userAgentType))
		{
			$request = Controller::getInstance()->getContext()->getRequest();
			if ($request->getMethod() == Request::CONSOLE)
			{
				$userAgentType = $request->getParameter('force-user-agent-type');
			}
			else
			{
				$userAgentType = $this->getBrowser()->GraphicalEngine;
			}

			if (is_null($userAgentType) || $userAgentType == '')
			{
				$userAgentType = 'all';
			}
			$this->m_userAgentType = $userAgentType;
		}

		return $this->m_userAgentType;
	}

	/**
	 * Get the current graphical engine version
	 * @return string (all, 4 ...)
	 */
	public function getUserAgentTypeVersion()
	{
		if (is_null($this->m_userAgentTypeVersion))
		{
			$request = Controller::getInstance()->getContext()->getRequest();
			if ($request->getMethod()==Request::CONSOLE)
			{
				$userAgentTypeVersion = $request->getParameter('force-user-agent-typeversion');
			}
			else
			{
				$userAgentTypeVersion = self::getBrowser()->GraphicalEngineVer;
			}

			if (is_null($userAgentTypeVersion) || $userAgentTypeVersion == '')
			{
				$userAgentTypeVersion = "all";
			}

			$this->m_userAgentTypeVersion = $userAgentTypeVersion;
		}
		return $this->m_userAgentTypeVersion;
	}

	/**
	 * @return String
	 */
	public function getCompleteUserAgent()
	{
		return $this->getUserAgentType() . '.' . $this->getUserAgentTypeVersion();
	}

	/**
	 * Get current context mime content-type
	 * @return string
	 */
	public function getMimeContentType()
	{
		if (is_null($this->m_mimeContentType))
		{
			$mimeContentType = Controller::getInstance()->getContext()->getRequest()->getParameter('contentType');
			if (!$mimeContentType)
			{
				$mimeContentType = K::HTML;
			}
			$this->m_mimeContentType = $mimeContentType;
		}
		return $this->m_mimeContentType;
	}

	/**
	 * Set current context mime content-type
	 * @param string $mimeContentType
	 */
	public function setMimeContentType($mimeContentType)
	{
		$this->m_mimeContentType = $mimeContentType;
	}

	/**
	 * Since Gecko 1.9 branch, XML PI require a different W3C compliant syntax.
	 *
	 * Cf. https://bugzilla.mozilla.org/show_bug.cgi?id=344488
	 * and https://bugzilla.mozilla.org/show_bug.cgi?id=347879
	 *
	 * @return boolean True if the browser requires the new XML PI policy.
	 */
	public function requiresCompliantXMLPI()
	{
		return ((strtolower($this->getUserAgentType()) == 'gecko') && (intval($this->getUserAgentTypeVersion()) >= 3));
	}

	/**
	 * Browser matcher :
	 * @see http://file.devlinux.france.rbs.fr/projects/webedit4/wiki/UserAgent
	 * @return boolean
	 */
	public function userAgentMatch($pattern, $uaString = null)
	{
		$pattern = trim(strtolower(str_replace(' ', '', $pattern)));

		if (is_null($uaString))
		{
			$uaType = $this->getUserAgentType();
			$uaVersion = $this->getUserAgentTypeVersion();
		}
		else
		{
			$uaString = explode(':', $uaString);
			$uaType = $uaString[0];
			if (count($uaString) === 1)
			{
				$uaVersion = 'all';
			}
			else
			{
				$uaVersion = $uaString[1];
			}
		}

		if (empty($pattern) || ($pattern == 'all') || (stripos($pattern, 'all:all') !== false))
		{
			return true;
		}

		if ($pattern == 'image')
		{
			return ($uaType == 'image');
		}

		$match = false;

		$patterns = explode('|', $pattern);
		foreach ($patterns as $pattern)
		{
			$browserPattern = explode(':', $pattern);
			if (count($browserPattern) == 1)
			{
				if (strpos($browserPattern[0], '!') !== false)
				{
					$doNeg = true;
					$browserPattern[0] = str_replace('!', '', $browserPattern[0]);
				}
				else
				{
					$doNeg = false;
				}
				if (empty($browserPattern[0]) || $browserPattern[0] == 'all')
				{
					$currentMatch = true;
				}
				else
				{
					$currentMatch = ($uaType == $browserPattern[0]);
				}
				if ($doNeg)
				{
					$currentMatch = !$currentMatch;
				}
			}
			else
			{
				$browserId = array_shift($browserPattern);
				if (strpos($browserId, '!') !== false)
				{
					$doNeg = true;
					$browserId = str_replace('!', '', $browserId);
				}
				else
				{
					$doNeg = false;
				}
				if (empty($browserId) || $browserId == 'all')
				{
					$currentMatch = true;
				}
				else
				{
					$currentMatch = ($uaType == $browserId);
				}
				foreach ($browserPattern as $browserVersion)
				{
					if (strpos($browserVersion, '!') !== false)
					{
						$doVersionNeg = true;
						$browserVersion = str_replace('!', '', $browserVersion);
					}
					else
					{
						$doVersionNeg = false;
					}
					if (empty($browserVersion) || $browserVersion == 'all')
					{
						$versionMatch = true;
					}
					else if ($uaVersion == $browserVersion)
					{
						$versionMatch = true;
					}
					else if (strpos($browserVersion, '+') !== false)
					{
						$currentPatternVersion = explode('.', str_replace('+', '', $browserVersion));
						$currentBrowserVersion = explode('.', $uaVersion);
						if ((count($currentPatternVersion) == 1) || (!isset($currentBrowserVersion[1])))
						{
							if ($currentBrowserVersion[0] >= $currentPatternVersion[0])
							{
								$versionMatch = true;
							}
							else
							{
								$versionMatch = false;
							}
						}
						else
						{
							if (($currentBrowserVersion[0] >= $currentPatternVersion[0]) && ($currentBrowserVersion[1] >= $currentPatternVersion[1]))
							{
								$versionMatch = true;
							}
							else
							{
								$versionMatch = false;
							}
						}
					}
					else if (strpos($browserVersion, '-') !== false)
					{
						$currentPatternVersion = explode('.', str_replace('-', '', $browserVersion));
						$currentBrowserVersion = explode('.', $uaVersion);
						if ((count($currentPatternVersion) == 1) || (!isset($currentBrowserVersion[1])))
						{
							if ($currentBrowserVersion[0] <= $currentPatternVersion[0])
							{
								$versionMatch = true;
							}
							else
							{
								$versionMatch = false;
							}
						}
						else
						{
							if (($currentBrowserVersion[0] <= $currentPatternVersion[0]) && ($currentBrowserVersion[1] <= $currentPatternVersion[1]))
							{
								$versionMatch = true;
							}
							else
							{
								$versionMatch = false;
							}
						}
					}
					else
					{
						$versionMatch = false;
					}
					if ($doVersionNeg)
					{
						$versionMatch = !$versionMatch;
					}
					$currentMatch &= $versionMatch;
				}
				if ($doNeg)
				{
					$currentMatch = !$currentMatch;
				}
			}
			$match |= $currentMatch;
		}
		return $match;
	}

	/**
	 * @return String
	 */
	public function getClientIp()
	{
		$ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null;
		$remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
		if (!f_util_StringUtils::isEmpty($ip) && !f_util_StringUtils::isEmpty($remoteAddr))
		{
			$ip .= ', '; 
		}
		$ip .= $remoteAddr;
		return $ip;
	}
}
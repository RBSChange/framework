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
	private $profile;
	

	/**
	 * @deprecated
	 */
	private $m_isLangDefined = false;


	protected function __construct()
	{
		$this->resetProfile();
	}
	
	/**
	 * @return RequestContext
	 */
	public static function getInstance()
	{
		if (self::$m_instance === null)
		{
			self::$m_instance = new RequestContext();
		}
		return self::$m_instance;
	}

	public static function clearInstance()
	{
		self::$m_instance = null;
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
	 * @param boolean $ajaxMode
	 * @param string $fromURL
	 * @return void
	 */
	public function setAjaxMode($ajaxMode, $fromURL)
	{
		$this->ajaxMode = $ajaxMode;
		$this->ajaxFromURL = $fromURL;
	}

	/**
	 * @return boolean
	 */
	public function getAjaxMode()
	{
		return $this->ajaxMode;
	}
	
	/**
	 * @return string
	 */
	public function getAjaxFromURL()
	{
		return $this->ajaxFromURL;
	}
	
	/**
	 * @return string
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
		
	public function resetProfile()
	{
		$this->profile = array('date' => array(), 'datetime' => array(), 'timezone' => null);
	}
	
	protected function getProfileValues()
	{
		$pref = change_Controller::getInstance()->getStorage()->readForUser('profilesvalues');
		return is_array($pref) ? $pref : null;
	}
	
	/**
	 * @param string $lang
	 * @return string
	 */
	public function getDateTimeFormat($lang)
	{
		if (!isset($this->profile['datetime'][$lang]))
		{
			$prefs = $this->getProfileValues();
			if ($prefs !== null && isset($prefs['datetimeformat']))
			{
				$this->profile['datetime'][$lang] = $prefs['datetimeformat'];
			}
			else
			{
				$this->profile['datetime'][$lang] = LocaleService::getInstance()->formatKey($lang, 'f.date.date.default-datetime-format');
			}
		}
	 	return $this->profile['datetime'][$lang];
	}	

	/**
	 * @param string $lang
	 * @return string
	 */
	public function getDateFormat($lang)
	{
		if (!isset($this->profile['date'][$lang]))
		{
			$prefs = $this->getProfileValues();
			if ($prefs !== null && isset($prefs['dateformat']))
			{
				$this->profile['date'][$lang] = $prefs['dateformat'];
			}
			else
			{
				$this->profile['date'][$lang] = LocaleService::getInstance()->formatKey($lang, 'f.date.date.default-date-format');
			}
		}
	 	return $this->profile['date'][$lang];
	}
	
	/**
	 * @return string
	 */
	public function getTimeZone()
	{
		if (!isset($this->profile['timezone']))
		{
			$prefs = $this->getProfileValues();
			if ($prefs !== null && isset($prefs['timezone']) && !empty($prefs['timezone']))
			{
				$this->profile['timezone'] = $prefs['timezone'];
			}
			else
			{
				$this->profile['timezone'] = DEFAULT_TIMEZONE;
			}   
		}
		return $this->profile['timezone'];	   
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
			$browscap = new Browscap();
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
	 * @return string ("windows", "macos", "linux", "other")
	 */
	public function getOperatingSystem()
	{
		if (is_null($this->m_os))
		{
			$this->m_os = self::OS_OTHER;
			if (isset($_SERVER['HTTP_USER_AGENT']))
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
			else
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
		}

		return $this->m_os;
	}

	/**
	 * Get the current graphical engine type
	 * @return string (all, gecko, trident, presto, khtml ...)
	 */
	public function getUserAgentType()
	{
		if (is_null($this->m_userAgentType))
		{
			$request = change_Controller::getInstance()->getContext()->getRequest();
			$userAgentType = $this->getBrowser()->GraphicalEngine;

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
			$request = change_Controller::getInstance()->getContext()->getRequest();
			$userAgentTypeVersion = $this->getBrowser()->GraphicalEngineVer;

			if (is_null($userAgentTypeVersion) || $userAgentTypeVersion == '')
			{
				$userAgentTypeVersion = "all";
			}

			$this->m_userAgentTypeVersion = $userAgentTypeVersion;
		}
		return $this->m_userAgentTypeVersion;
	}
	
	/**
	 * @param string $type
	 * @param string $version
	 */
	public function setCompleteUserAgent($type, $version = 'all')
	{
		$this->m_userAgentType = $type;
		$this->m_userAgentTypeVersion = $version;
	}
	
	/**
	 * @return string
	 */
	public function getCompleteUserAgent()
	{
		return $this->getUserAgentType() . '.' . $this->getUserAgentTypeVersion();
	}

	/**
	 * Get current context mime content-type
	 * @return string 'html'|'xul'
	 */
	public function getMimeContentType()
	{
		if (is_null($this->m_mimeContentType))
		{
			$mimeContentType = change_Controller::getInstance()->getContext()->getRequest()->getParameter('contentType');
			if (!$mimeContentType)
			{
				$mimeContentType = 'html';
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
	 * @deprecated
	 */
	public function getClientIp()
	{
		$controller = change_Controller::getInstance();
		$controller->getClientIp();
	}
	
	
	/**
	 * @deprecated use \Change\Mvc\Context::BACKEND
	 */
	const BACKOFFICE_MODE = 0;
	
	/**
	 * @deprecated use \Change\Mvc\Context::FRONTEND
	 */	
	const FRONTOFFICE_MODE = 1;
	
	
	/**
	 * @deprecated
	 */
	public function getMode()
	{
		$controller = change_Controller::getInstance();
		$controller->getContext()->getMode();
	}
	
	/**
	 * @deprecated
	 */
	public function setMode($mode)
	{
		$controller = change_Controller::getInstance();
		$controller->getContext()->setMode($mode);
	}
	
	/**
	 * @deprecated
	 */
	public function inHTTPS()
	{
		return $this->getProtocol() === 'https';
	}
	
	/**
	 * @deprecated
	 */
	public function getProtocol()
	{
		$controller = change_Controller::getInstance();
		return $controller->getUri()->getScheme();
	}
	
	/**
	 *  @deprecated
	 */
	public function getPathURI()
	{
		$controller = change_Controller::getInstance();
		$uri = $controller->getUri();
		return $uri->getPath() . ($uri->getQuery() ? '?' . $uri->getQuery() : ''); 
	}
	
	/**
	 * @deprecated
	 */
	public function hasI18nSynchro()
	{
		return \Change\I18n\I18nManager::getInstance()->hasI18nSynchro();
	}
	
	/**
	 * @deprecated
	 */
	public function getI18nSynchro()
	{
		return \Change\I18n\I18nManager::getInstance()->getI18nSynchro();
	}
	
	/**
	 * @deprecated
	 */
	public function isMultiLangEnabled()
	{
		return \Change\I18n\I18nManager::getInstance()->isMultiLangEnabled();
	}
	
	/**
	 * @deprecated
	 */
	public function isLangDefined()
	{
		return $this->m_isLangDefined;
	}
	
	/**
	 * @deprecated
	 */
	public function getLang()
	{
		return \Change\I18n\I18nManager::getInstance()->getLang();
	}
	
	/**
	 * @deprecated
	 */
	public function getUILang()
	{
		return \Change\I18n\I18nManager::getInstance()->getUILang();
	}
	
	/**
	 * @deprecated
	 */
	public function setLang($lang)
	{
		$this->m_isLangDefined = \Change\I18n\I18nManager::getInstance()->setLang($lang);
	}
	
	/**
	 * @deprecated
	 */
	public function setUILang($lang)
	{
		return \Change\I18n\I18nManager::getInstance()->setUILang($lang);
	}
	
	/**
	 * @deprecated
	 */
	public function setUILangFromParameter($lang)
	{
		if ($lang && is_string($lang) && in_array($lang, $this->getUISupportedLanguages()))
		{
			$this->setUILang($lang);
		}
	}
	
	/**
	 * @deprecated
	 */
	public function getUserAgentLanguage()
	{
		$controller = change_Controller::getInstance();
		$lang = $controller->getUserAgentLanguage();
		if ($lang === null || !in_array($lang, $this->getUISupportedLanguages()))
		{
			$lang = $this->getUIDefaultLang();
		}
		return $lang;
	}
		
	/**
	 * @deprecated
	 */
	public function getSupportedLanguages()
	{
		return \Change\I18n\I18nManager::getInstance()->getSupportedLanguages();
	}
	
	/**
	 * @deprecated
	 */
	public function getUISupportedLanguages()
	{
		return \Change\I18n\I18nManager::getInstance()->getUISupportedLanguages();
	}
	
	/**
	 * @deprecated
	 */
	public function getDefaultLang()
	{
		return \Change\I18n\I18nManager::getInstance()->getDefaultLang();
	}
	
	/**
	 * @deprecated
	 */
	public function getUIDefaultLang()
	{
		return \Change\I18n\I18nManager::getInstance()->getUIDefaultLang();
	}
	
	/**
	 * @deprecated
	 */
	public function beginI18nWork($lang)
	{
		\Change\I18n\I18nManager::getInstance()->pushLang($lang);
	}
	
	/**
	 * @deprecated
	 */
	public function endI18nWork($exception = null)
	{
		\Change\I18n\I18nManager::getInstance()->popLang($exception);
	}
}

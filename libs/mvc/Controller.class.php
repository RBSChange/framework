<?php
/**
 * @deprecated use \Change\Mvc\Controller
 * @method change_Context getContext()
 * @method change_Request getRequest()
 */
class change_Controller extends \Change\Mvc\Controller
{

	/**
	 * @deprecated
	 */
	const AUTO_LOGIN_COOKIE = 'autologin';
	
	/**
	 * @deprecated
	 */
	protected $contentType;
	
	/**
	 * @deprecated
	 */
	public function getContentType()
	{
		return $this->contentType;
	}
	
	/**
	 * @deprecated
	 */
	public function setContentType($type)
	{
		$this->contentType = $type;	
	}

	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @return string|null
	 */
	protected function getActionClassName($moduleName, $actionName)
	{
		$actionClassName = $moduleName . '_' . $actionName . 'Action';
		$exist = class_exists($actionClassName);	
		if (!$exist && $moduleName !== 'generic')
		{
			return $this->getActionClassName('generic', $actionName);
		}
		return $exist ? $actionClassName : null;
	}

	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @return string|null
	 */
	protected function getViewClassName($moduleName, $actionName)
	{
		$viewClassName = $moduleName . '_' . $actionName . 'View';
		return class_exists($viewClassName) ? $viewClassName : null;
	}

	/**
	 * @return void
	 */
	public function dispatch()
	{
		$request = $this->getRequest();
		$lang = $request->getParameter('lang');
		
		if (!empty($lang))
		{
			$requestContext = RequestContext::getInstance();
			$requestContext->setLang($lang);
		}
		
		if ($this->allowAutoLogin() && isset($_COOKIE[self::AUTO_LOGIN_COOKIE]))
		{
			// Handle auto-login.
			$us = users_UserService::getInstance();
			if ($us->getCurrentUser() === null)
			{
				$autoLoginInfos = $_COOKIE[self::AUTO_LOGIN_COOKIE];
				$login = $autoLoginInfos['login'];
				$passwd = $autoLoginInfos['passwd'];
	
				$website = website_WebsiteService::getInstance()->getCurrentWebsite();
				$users = $us->getUsersByLoginAndGroup($login, $website->getGroup());
				$ok = false;
				foreach ($users as $user)
				{
					if (sha1($user->getPasswordmd5()) == $passwd)
					{
						if (Framework::isInfoEnabled())
						{
							Framework::info(__METHOD__ . ' auto-login with ' . $login);
						}
						$us->authenticate($user);
						$ok = true;
						break;
					}
				}
	
				if (!$ok)
				{
					if (Framework::isInfoEnabled())
					{
						Framework::info(__METHOD__ . ' auto-login skipped because the user with login ' . $login . ' doesn\'t exist, is not published or the password is wrong.');
					}
					users_ModuleService::getInstance()->unsetAutoLogin();
				}
			}
		}
		parent::dispatch();
	}
	
	/**
	 * @deprecated
	 */
	public function allowAutoLogin()
	{
		return true;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 */
	public function forward($moduleName, $actionName)
	{
		try
		{
			parent::forward($moduleName, $actionName);
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			f_web_http_Header::setStatus('500');
			$r = new exception_HtmlRenderer();
			$r->printStackTrace($e);
			$this->shutdown();
			exit(-1);
		}		
	}
		
	protected function loadContext()
	{
		$this->context = new change_Context($this);
	}
	
	public static function setNoCache()
	{
		static::getInstance()->addNoCacheHeader();
	}
		
	/**
	 * @return change_Controller
	 */
	public static function getInstance()
	{
		return \Change\Application::getInstance()->getApplicationServices()->getController();
	}
	
	/**
	 * @param string $className
	 * @throws Exception
	 * @return change_Controller
	 */
	public static function newInstance($className)
	{
		$application = \Change\Application::getInstance();
		$instance = null;
		if ($className != get_called_class())
		{
			if (class_exists($className))
			{
				$instance = new $className($application);
				$application->getApplicationServices()->instanceManager()->addSharedInstance($instance, 'Change\Mvc\Controller');
			}
		}
		
		if ($instance === null)
		{
			$instance = new static($application);
			$application->getApplicationServices()->instanceManager()->addSharedInstance($instance, 'Change\Mvc\Controller');
		}
		return $instance;
	}
}
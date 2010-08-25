<?php

/**
 * HttpController allows you to centralize your entry point in your web
 * application, but at the same time allow for any module and action combination
 * to be requested.
 */
class HttpController extends WebController
{
	private static $globalContext = null;

	public static function getGlobalContext()
	{
		return HttpController::$globalContext;
	}

	private function setGlobalContext($context = null)
	{
		if ($context === null) {
			HttpController::$globalContext = $this->getContext();
		} else {
			HttpController::$globalContext = $context;
		}
	}
	
	public function __construct()
	{
		// initialize the controller
	    $this->initialize();

		// set "global" context
		$this->setGlobalContext();  
	}
	
	public function shutdown ()
	{
		parent::shutdown();
		//LoggerManager::shutdown();
	}
	
	
    // +-----------------------------------------------------------------------+
    // | METHODS                                                               |
    // +-----------------------------------------------------------------------+

    /**
     * Dispatch a request.
     */
    public function dispatch ()
    {
 		// get the application context
	    $context = $this->getContext();
	    // determine our module and action
	    $moduleName = $context->getRequest()
				  ->getParameter(AG_MODULE_ACCESSOR);

	    $actionName = $context->getRequest()
				  ->getParameter(AG_ACTION_ACCESSOR);
		
		if ($moduleName == null)
		{
			// no module has been specified
			$moduleName = AG_DEFAULT_MODULE;
		}
		
		if ($actionName == null)
		{
			// no action has been specified
			if ($this->actionExists($moduleName, AG_DEFAULT_ACTION))
			{
				 // an Index action exists
			   	$actionName = AG_DEFAULT_ACTION;
			}
			else
			{
			    // use the default action
			    $actionName = AG_ERROR_404_ACTION;
			}
	    }
	    // make the first request
	    $this->forward($moduleName, $actionName);
    }
    
    /**
     * Overrided function
     */
	public function genURL ($url = null, $parameters = array())
	{
		if ($url === null)
		{
			$url = $_SERVER['SCRIPT_NAME'];
		}

		if (AG_URL_FORMAT == 'PATH')
		{
			// use PATH format
			$divider  = '/';
			$equals   = '/';
			$url     .= '/';
		}
		else
		{
			// use GET format
			$divider  = '&';
			$equals   = '=';
			if ( ! empty($parameters) )
			{
				if ( strpos($url, "?") !== false)
				{
					$url .= $divider;
				}
				else
				{
					$url .= '?';
				}
			}
		}

		// loop through the parameters
		foreach ($parameters as $key => &$value)
		{
			if ( is_array($value) )
			{
				foreach ($value as $key2 => &$val)
				{
					if (is_string($val) || is_numeric($val) )
					{
						$url .= urlencode($key)."[$key2]" . $equals . urlencode($val) . $divider;
					}
				}
			}
			else
			{
				if ( is_string($value) || is_numeric($value) )
				{
					$url .= urlencode($key) . $equals . urlencode($value) . $divider;
				}
			}
		}

		// strip off last divider character
		$url = rtrim($url, $divider);

		// replace &'s with &amp;
		$url = str_replace('&', '&amp;', $url);

		return $url;

	}
	
	public function redirect($moduleName, $actionName)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("Controller->redirect($moduleName, $actionName)", 'controller');
		}
		
		$request = $this->getContext()->getRequest();
		
		$requestParametersNames = $request->getParameterNames();
		
		$urlParam = array();
		foreach ($requestParametersNames as $requestParameterName)
		{
			if (is_string($request->getParameter($requestParameterName)))
			{
				$urlParam[$requestParameterName] = $request->getParameter($requestParameterName);
			}
		}
		
		$urlParam['module'] = $moduleName;
		$urlParam['action'] = $actionName;
		
		$url = $this->generateRedirectUrl($urlParam);
		
		if (Framework::isDebugEnabled())
		{
			Framework::debug("Controller->redirect ; resulting URL : $url", 'controller');
		}
		
		// shutdown the controller
		$this->shutdown();
		if (!headers_sent())
		{
			header('Location: ' . $url);
		}
		else
		{
			if (Framework::isWarnEnabled())
			{
				Framework::warn('Controller->redirect called while header already sent', 'controller');
			}
		}
		exit();
	}
	    
	public function actionExists($moduleName, $actionName)
	{
		return f_util_ClassUtils::classExists(strtolower($moduleName) . '_' . $actionName . "Action");
	}
	
	public function getAction($moduleName, $actionName)
	{
		$className = $moduleName . '_' . $actionName . 'Action';
		ClassLoader::getInstance()->load($className);	
		return new $className();
	}

	public function viewExists($moduleName, $viewName)
	{
		return f_util_ClassUtils::classExists($moduleName . '_' . $viewName . 'View');
	}
	
	public function getView($moduleName, $viewName)
	{
		$className = $moduleName . '_' . $viewName . 'View';
		ClassLoader::getInstance()->load($className);	
		return new $className();
	}
	
	public function forward($moduleName, $actionName)
	{
		$this->pushEffectiveModuleName($moduleName);
		try
		{
			if (!$moduleName !== K::GENERIC_MODULE_NAME && !$this->actionExists($moduleName, $actionName))
			{
				$this->getRequest()->setParameter(K::WEBEDIT_MODULE_ACCESSOR, $moduleName);
				$moduleName = K::GENERIC_MODULE_NAME;
			}
			parent::forward($moduleName, $actionName);
			$this->popEffectiveModuleName();
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			$this->popEffectiveModuleName();
		}			
	}
	
	public function modelExists($moduleName, $modelName)
	{
		return false;	
	}
	
	/**
	 * @return Request
	 */
	private function getRequest()
	{
		return $this->getContext()->getRequest();
	}
	
	/**
	 * @param String $moduleName
	 */
	private function pushEffectiveModuleName($moduleName)
	{
		$moduleStack = $this->getModuleStack();
		$moduleStack[] = $moduleName;
		$this->getRequest()->setAttribute(K::EFFECTIVE_MODULE_NAME, $moduleStack);
	}
	
	/**
	 * @return String[]
	 */
	private function getModuleStack()
	{
		$moduleStack = null;
		if (!$this->getRequest()->hasAttribute(K::EFFECTIVE_MODULE_NAME))
		{
			$moduleStack = array();
		}
		else
		{
			$moduleStack = $this->getRequest()->getAttribute(K::EFFECTIVE_MODULE_NAME);
		}
		return $moduleStack;
	}
	
	/**
	 */
	private function popEffectiveModuleName()
	{
		$moduleStack = $this->getModuleStack();
		array_pop($moduleStack);
		$this->getRequest()->setAttribute(K::EFFECTIVE_MODULE_NAME, $moduleStack);
	}
	
	/**
	 * @param array $urlParams
	 */
	protected function generateRedirectUrl($urlParams)
	{
		$encodedParam = array();
		foreach ($urlParams as $name => $value)
		{
			$encodedParam[] = $name . '=' . urlencode($value);
		}
		
		$stringParam = implode('&', $encodedParam);
		return 'http://' . $_SERVER['HTTP_HOST'] . '/index.php?' . $stringParam;
	}
	
	/*The request to another location.
	*
	* @param string A URL.
	*
	* @access public
	* @since  2.0
	*/
	public function redirectToUrl($url)
	{
		
		// shutdown the controller
		$this->shutdown();
		if (headers_sent() == false)
		{
			header('Location: ' . $url);
		}
		exit();
	}
}
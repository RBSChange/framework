<?php
class change_Controller
{

	/**
	 * @var change_ActionStack
	 */
	private $actionStack = null; 

	/**
	 * @var integer;
	 */
	private $maxForwards = 20; 
	
	/**
	 * @var change_Request
	 */
	private $request = null; 
	
	/**
	 * @var change_Storage
	 */	
	private $storage = null; 
	
	/**
	 * @var change_User
	 */
	private $user = null;
	
	/**
	 * @var change_Context
	 */
	protected $context = null;
	
	/**
	 * @var change_Controller
	 */
	private static $instance = null;
	
	
	protected function __construct()
	{
		// initialize the controller
	    $this->initialize();
	}
		
	public function dispatch()
	{
		$this->setLangFromRequest();
		$this->setRequestContextMode();
		
 		// get the application context
	    $context = $this->getContext();
	    // determine our module and action
	    $moduleName = $context->getRequest()->getParameter('module');
    	if ($moduleName == null)
		{
			$moduleName = 'website';
		}
		
	    $actionName = $context->getRequest()->getParameter('action');
		if ($actionName == null)
		{
			// no action has been specified
			if ($this->actionExists($moduleName, 'Index'))
			{
				 // an Index action exists
			   	$actionName = 'Index';
			}
			else
			{
			    $moduleName =  'website';
			    $actionName = 'Error404';
			}
	    }
	    // make the first request
	    $this->forward($moduleName, $actionName);
	}
	
	/**
	 * Set the context lang
	 */
	private function setLangFromRequest()
	{
		$request = $this->getRequest();
		$lang = $request->getParameter('lang');
		if (!f_util_StringUtils::isEmpty($lang))
		{
			$requestContext = RequestContext::getInstance();
			$requestContext->setLang($lang);
		}
	}
	
	protected function setRequestContextMode()
	{
	    RequestContext::getInstance()->setMode(RequestContext::FRONTOFFICE_MODE);
	}
	
	/**
	 * @return string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}
	
	/**
	 * @param string $type
	 */
	public function setContentType($type)
	{
		$this->contentType = $type;	
	}

	
	/**
	 * 
	 * @param string $moduleName
	 * @param string $actionName
	 */
	public function actionExists($moduleName, $actionName)
	{
		return f_util_ClassUtils::classExists(strtolower($moduleName) . '_' . $actionName . 'Action');
	}
	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @return change_Action
	 */
	protected function getAction($moduleName, $actionName)
	{
		$className = $moduleName . '_' . $actionName . 'Action';
		ClassLoader::getInstance()->load($className);	
		return new $className();
	}

	/**
	 * @param string $moduleName
	 * @param string $viewName
	 * @return boolean
	 */
	public function viewExists($moduleName, $viewName)
	{
		return f_util_ClassUtils::classExists($moduleName . '_' . $viewName . 'View');
	}
	
	/**
	 * @param string $moduleName
	 * @param string $viewName
	 * @return change_View
	 */
	protected function getView($moduleName, $viewName)
	{
		$className = $moduleName . '_' . $viewName . 'View';
		ClassLoader::getInstance()->load($className);	
		return new $className();
	}
	
	
	/**
	 * @return change_Request
	 */
	public function getRequest()
	{
		return $this->getContext()->getRequest();
	}
		
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 */
	public function forward($moduleName, $actionName)
	{
		$actionModuleName = $moduleName;
		try
		{
			if ($this->actionExists($actionModuleName, $actionName))
			{
				$actionInstance = $this->getAction($actionModuleName, $actionName);
			}
			elseif ($actionModuleName !== 'generic' && $this->actionExists('generic', $actionName))
			{
				$this->getRequest()->setParameter('wemod', $moduleName);		
				$actionModuleName = 'generic';		
				$actionInstance =  $this->getAction('generic', $actionName);
			}
			else if ($moduleName !== 'website' && $actionName !== 'Error404')
			{
				return $this->forward('website', 'Error404');
			}
			else
			{
				throw new Exception('Action not found: ' . $moduleName. '/'. $actionName);
			}

			if ($this->actionStack->getSize() >= $this->maxForwards)
			{
				throw new Exception('Too many forwards have been detected for this request');
			}
			
			$this->actionStack->addEntry($moduleName, $actionName, $actionInstance);
			
				// initialize the action
			if ($actionInstance->initialize($this->context))
			{	
				// create a new filter chain		
				if ($actionInstance->isSecure())
				{
					if (!$this->user->isAuthenticated())
					{
						$this->forward('users', 'Login');
						return;
					}
				}
				
				$method = $this->request->getMethod();								
				if (($actionInstance->getRequestMethods() & $method) != $method)
				{
					$viewName = $actionInstance->getDefaultView();
				} 
				else
				{
					if ($actionInstance->validate())
					{
						$viewName = $actionInstance->execute();
					} 
					else
					{
						$viewName = $actionInstance->handleError();
					}
				}
	
				if ($viewName != change_View::NONE)
				{
		
					if (is_array($viewName))
					{
						list ($moduleName, $viewName) = $viewName;		
					} 
					else
					{
						$viewName = $actionName . $viewName;
					}
		
					// display this view
					if (!$this->viewExists($moduleName, $viewName))
					{
						throw new Exception('Module "'.$moduleName .'" does not contain the view "'. $viewName. 'View"');
					}
		
					// get the view instance
					$viewInstance = $this->getView($moduleName, $viewName);
		
					// initialize the view
					if ($viewInstance->initialize($this->context))
					{
		
						// view initialization completed successfully
						$viewInstance->execute();
						$viewInstance->render();
					} 
					else
					{
						throw new Exception('View initialization failed for module "'.$moduleName .'", view '. $viewName. 'View"');
					}
				}
			}
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

	/**
	 * @return change_ActionStack
	 */
	public function getActionStack()
	{
		return $this->actionStack;
	}
	
	/**
	 * @return change_Context
	 */
	public function getContext()
	{
		return $this->context;
	}

		
	/**
	 * @return change_Storage
	 */
	public function getStorage()
	{
		return $this->storage;
	}
	
	/**
	 * @return change_User
	 */
	public function getUser()
	{
		return $this->user;
	}
	
	
	/**
	 *
	 * @return change_Controller 
	 */
	public static function getInstance()
	{	
		if (null !== self::$instance)
		{
			return self::$instance;
		}
		return self::newInstance('change_Controller');
	}
	
	protected function initialize()
	{
		$this->loadContext();
		$this->actionStack = $this->context->getActionStack();
		$this->request = $this->context->getRequest();
		$this->user = $this->context->getUser();
		$this->storage = $this->context->getStorage();
		
		register_shutdown_function(array($this, 'shutdown'));
	}
	
	protected function loadContext()
	{
		$this->context = change_Context::getInstance($this);
	}
	
	/**
	 * 
	 * @param string $moduleName
	 * @param string $actionName
	 * @param array $parameters
	 */
	public function redirect($moduleName, $actionName, $parameters = null)
	{
		$request = $this->getContext()->getRequest();	
		$requestParametersNames = $request->getParameterNames();
		if (is_array($parameters))
		{
			$urlParam = $parameters;	
		}	
		else
		{
			$urlParam = array();
			foreach ($requestParametersNames as $requestParameterName)
			{
				if (is_string($request->getParameter($requestParameterName)))
				{
					$urlParam[$requestParameterName] = $request->getParameter($requestParameterName);
				}
			}
		}
		
		$urlParam['module'] = $moduleName;
		$urlParam['action'] = $actionName;
		
		$url = $this->generateRedirectUrl($urlParam);				
		$this->redirectToUrl($url);
	}
	
	/**
	 * @param string $url
	 * @param integer $delay
	 */
	public function redirectToUrl($url, $delay = 0)
	{
		// shutdown the controller
		$this->shutdown();
		if (!headers_sent())
		{
			header('Location: ' . $url);
		}
		echo '<html><head><meta http-equiv="refresh" content="',$delay,';url=', $url, '"/></head></html>';		
		exit();	
	}
	
	public static function setNoCache()
	{
		if (headers_sent())
		{
            return;
        }
        
        header('Last-Modified: '. gmdate('D, d M Y H:i:s') . ' GMT');
        header('Expires: '. 'Mon, 26 Jul 1997 05:00:00 GMT');
        header('Cache-Control: '. 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: '. 'no-cache');
        if (defined('NODE_NAME'))
        {
        	header('X-Change-WebNode: '. NODE_NAME);
        }
	}

	public static function newInstance($class)
	{
		try
		{
			if (null === self::$instance)
			{
				$controllerClassName = Framework::getConfiguration('mvc/classes/'.$class);
				$controller = new $controllerClassName();
				if (!($controller instanceof change_Controller))
				{
					$error = 'Class "%s" is not of the type Controller';
					$error = sprintf($error, $controllerClassName);	
					throw new Exception($error);
				}
				
				self::$instance = $controller;	
				return self::$instance;
			}
			else
			{
				$error = 'A Controller implementation instance has already been created';
				throw new Exception($error);			
			}
		}
		catch (Exception $e)
		{
			echo $e->getMessage() . "\n";
			echo $e->getTraceAsString() . "\n";
		}
	}
	
	public function shutdown()
	{	
		$this->user->shutdown();
		$this->storage->shutdown();	
		$this->request->shutdown();	
	}
    	
	/**
	 * @param array $urlParams
	 */
	protected function generateRedirectUrl($urlParams)
	{
	    return LinkHelper::getParametrizedLink($urlParams)->getUrl();	    
	}
}

class change_XulController extends change_Controller 
{
    
	protected function setRequestContextMode()
	{
	    RequestContext::getInstance()->setMode(RequestContext::BACKOFFICE_MODE);
	    $this->getContext()->getUser()->setUserNamespace(change_User::BACKEND_NAMESPACE);
	}
	
	/**
	 * @param array $urlParams
	 */
	protected function generateRedirectUrl($urlParams)
	{
	    return LinkHelper::getUIParametrizedLink($urlParams)->getUrl();	    
	}
}
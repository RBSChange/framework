<?php
class Context
{
	/**
	 * @var Context
	 */
	protected static $instance = null;
	
	/**
	 * @var ActionStack
	 */
	protected $actionStack = null;
	
	/**
	 * @var Controller
	 */
	protected $controller = null;
	
	/**
	 * @var ChangeRequest
	 */
	protected $request = null;
	
	/**
	 * @var ChangeSessionStorage
	 */
	protected $storage = null;
	
	/**
	 * 
	 * @var FrameworkSecurityUser
	 */
	protected $user = null;
	
	protected function __construct() 
	{

	}

	public function getActionName ()
	{

		// get the last action stack entry
		$actionEntry = $this->actionStack->getLastEntry();

		return $actionEntry->getActionName();

	}

	public function getActionStack()
	{
		return $this->actionStack;
	}

	
	public function getController ()
	{

		return $this->controller;

	}

	public static function getInstance($controller)
	{
		if (self::$instance === null) 
		{
			self::$instance = new self();
			self::$instance->controller 			= $controller;
			self::$instance->actionStack			= new ActionStack();

			self::$instance->request =  new ChangeRequest();
			self::$instance->storage = new ChangeSessionStorage();
			self::$instance->user = new FrameworkSecurityUser();
			
			self::$instance->request->initialize(self::$instance, null);
			self::$instance->storage->initialize(self::$instance, null);
			self::$instance->user->initialize(self::$instance, null);
		}
		return self::$instance;
	}

	public function getModuleDirectory()
	{
		$actionEntry = $this->actionStack->getLastEntry();
		return AG_MODULE_DIR . '/' . $actionEntry->getModuleName();
	}

	public function getModuleName ()
	{

		// get the last action stack entry
		$actionEntry = $this->actionStack->getLastEntry();

		return $actionEntry->getModuleName();

	}

	public function getRequest ()
	{
		return $this->request;
	}
	
	public function getStorage ()
	{
		return $this->storage;
	}

	public function getUser ()
	{
		return $this->user;
	}
}
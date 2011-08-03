<?php
class change_Context
{
	/**
	 * @var change_Context
	 */
	protected static $instance = null;
	
	/**
	 * @var change_ActionStack
	 */
	protected $actionStack = null;
	
	/**
	 * @var change_Controller
	 */
	protected $controller = null;
	
	/**
	 * @var change_Request
	 */
	protected $request = null;
	
	/**
	 * @var change_Storage
	 */
	protected $storage = null;
	
	/**
	 * 
	 * @var change_User
	 */
	protected $user = null;
	
	
	protected function __construct() 
	{
	}
	
	/**
	 * @return change_ActionStack
	 */
	public function getActionStack()
	{
		return $this->actionStack;
	}

	/**
	 * @return string
	 */
	public function getActionName()
	{
		// get the last action stack entry
		$actionEntry = $this->actionStack->getLastEntry();
		return $actionEntry->getActionName();
	}
	
	/**
	 * @return string
	 */
	public function getModuleName()
	{
		// get the last action stack entry
		$actionEntry = $this->actionStack->getLastEntry();
		return $actionEntry->getModuleName();
	}

	/**
	 * @param change_Controller $controller
	 * @return change_Context
	 */
	public static function getInstance($controller)
	{
		if (self::$instance === null) 
		{
			self::$instance = new self();
			self::$instance->controller 			= $controller;
			self::$instance->actionStack			= new change_ActionStack();

			$classes = Framework::getConfiguration('mvc/classes');
			self::$instance->request = new $classes['change_Request'];
			self::$instance->storage = new $classes['change_Storage'];
			self::$instance->user = new $classes['change_User'];
			
			self::$instance->request->initialize(self::$instance, null);
			self::$instance->storage->initialize(self::$instance, null);
			self::$instance->user->initialize(self::$instance, null);
		}
		return self::$instance;
	}

	/**
	 * @return change_Controller
	 */
	public function getController ()
	{
		return $this->controller;
	}

	/**
	 * @return change_Request
	 */
	public function getRequest ()
	{
		return $this->request;
	}
	
	/**
	 * @return change_Storage
	 */
	public function getStorage ()
	{
		return $this->storage;
	}

	/**
	 * @return change_User
	 */
	public function getUser ()
	{
		return $this->user;
	}
}
<?php
class change_Context extends change_Singleton
{

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
	public static function getInstance($controller = null)
	{
		$instance = self::getInstanceByClassName(get_called_class());
		if ($controller !== null) 
		{
			$instance->controller = $controller;
			$instance->actionStack = new change_ActionStack();

			$instance->request = new change_Request();
			$instance->storage = new change_Storage();
			$instance->user = new change_User();
			
			$instance->request->initialize($instance, null);
			$instance->storage->initialize($instance, null);
			$instance->user->initialize($instance, null);
		}
		return $instance;
	}

	/**
	 * @return change_Controller
	 */
	public function getController()
	{
		return $this->controller;
	}

	/**
	 * @return change_Request
	 */
	public function getRequest()
	{
		return $this->request;
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
}
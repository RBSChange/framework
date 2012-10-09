<?php
/**
 * @deprecated use \Change\Mvc\Context
 * @method change_Request getRequest()
 */
class change_Context extends \Change\Mvc\Context
{
	/**
	 * @param change_Controller $controller
	 * @return change_Context
	 */
	protected function initialize($controller)
	{
		if ($controller !== null) 
		{
			$this->controller = $controller;

			$this->request = new change_Request();
			$this->storage = new change_Storage(\Change\Application::getInstance()->getApplicationServices()->getLogging());
			$this->user = new change_User();
			
			$this->request->initialize($this, null);
			$this->storage->initialize($this, null);
			$this->user->initialize($this, null);
		}
	}
}
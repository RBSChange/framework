<?php
/**
 * @deprecated use \Change\Mvc\User
 */
class change_User extends \Change\Mvc\User
{
	/**
	 *
	 * @param string $class
	 * @return change_User 
	 */
	public static function newInstance($class)
	{
		$object = new $class();
		if (!($object instanceof change_User))
		{
			$error = 'Class "'.$class.'" is not of the type change_User';
			throw new Exception($error);
		}
		return $object;
	}
	
	/**
	 * @deprecated
	 */
	protected function checkLoadedNamespace($ns = null)
	{
		// No implementation
		return false;
	}

	/**
	 * @deprecated
	 */
	public function clearAttributes()
	{
		Framework::error('Call to deprecated method ' . get_class($this) . '->clearAttributes');
		change_Controller::getInstance()->getStorage()->clearForUser();
	}
	
	/**
	 * @deprecated
	 */
	public function hasAttribute($name, $ns = null)
	{
		Framework::error('Call to deprecated method ' . get_class($this) . '->hasAttribute');
		if ($ns)
		{
			$container = new \Zend\Session\Container($ns);
			return change_Controller::getInstance()->getStorage()->readForContainer($name, $container);
		}
		return change_Controller::getInstance()->getStorage()->readForUser($name);
	}
	
	/**
	 * @deprecated
	 */
	public function removeAttribute($name, $ns = null)
	{
		Framework::deprecated('Call to deprecated method ' . get_class($this) . '->removeAttribute');
		$retval = null;
		if ($ns)
		{
			$container = new \Zend\Session\Container($ns);
			$retval = change_Controller::getInstance()->getStorage()->readForContainer($name, $container);
			change_Controller::getInstance()->getStorage()->removeForContainer($name, $container);
		}
		else
		{
			$retval = change_Controller::getInstance()->getStorage()->readForUser($name);
			change_Controller::getInstance()->getStorage()->removeForUser($name);
		}
		return $retval;
	}
		
	/**
	 * @deprecated
	 */
	public function getAttribute($name, $ns = null)
	{
		Framework::deprecated('Call to deprecated method ' . get_class($this) . '->getAttribute');
		if ($ns)
		{
			$sessionContainer = new \Zend\Session\Container($ns);
			return change_Controller::getInstance()->getStorage()->readForContainer($name, $sessionContainer);
		}
		return change_Controller::getInstance()->getStorage()->readForUser($name);
	}
	
	/**
	 * @deprecated
	 */
	public function getAttributeNames($ns = null)
	{
		Framework::deprecated('Call to deprecated method ' . get_class($this) . '->getAttributeNames');
		if ($ns)
		{
			$sessionContainer = new \Zend\Session\Container($ns);
		}
		else
		{
			$sessionContainer = change_Controller::getInstance()->getStorage()->getUserSessionContainer();
		}
		return array_keys($sessionContainer->getIterator());
	}
		
	/**
	 * @deprecated
	 */
	public function setAttribute($name, $value, $ns = null)
	{
		Framework::deprecated('Call to deprecated method ' . get_class($this) . '->setAttribute');
		if ($ns)
		{
			$sessionContainer = new \Zend\Session\Container($ns);
		}
		else
		{
			$sessionContainer = change_Controller::getInstance()->getStorage()->getUserSessionContainer();
		}
		change_Controller::getInstance()->getStorage()->writeForContainer($name, $value, $sessionContainer);
	}

	/**
	 * @deprecated
	 */
	public function setAttributeByRef($name, &$value, $ns = null)
	{
		Framework::deprecated('Call to deprecated method ' . get_class($this) . '->setAttributeByRef');
		if ($ns)
		{
			$sessionContainer = new \Zend\Session\Container($ns);
		}
		else
		{
			$sessionContainer = change_Controller::getInstance()->getStorage()->getUserSessionContainer();
		}
		change_Controller::getInstance()->getStorage()->writeForContainer($name, $value, $sessionContainer);
	}

	/**
	 * @deprecated
	 */
	public function setAttributes($attributes, $ns = null)
	{
		Framework::deprecated('Call to deprecated method ' . get_class($this) . '->setAttributes');
		if ($ns)
		{
			$sessionContainer = new \Zend\Session\Container($ns);
		}
		else
		{
			$sessionContainer = change_Controller::getInstance()->getStorage()->getUserSessionContainer();
		}
		foreach ($attributes as $name => $value)
		{
			change_Controller::getInstance()->getStorage()->writeForContainer($name, $value, $sessionContainer);
		}
	}
	
	/**
	 * @deprecated
	 */
	public function setAttributesByRef (&$attributes, $ns = null)
	{
		Framework::deprecated('Call to deprecated method ' . get_class($this) . '->setAttributesByRef');
		if ($ns)
		{
			$sessionContainer = new \Zend\Session\Container($ns);
		}
		else
		{
			$sessionContainer = change_Controller::getInstance()->getStorage()->getUserSessionContainer();
		}
		foreach ($attributes as $name => $value)
		{
			change_Controller::getInstance()->getStorage()->writeForContainer($name, $value, $sessionContainer);
		}
	}

	/**
	 * @deprecated
	 */
	public function getAttributeNamespace($ns = null)
	{
		Framework::deprecated('Call to deprecated method ' . get_class($this) . '->getAttributeNamespace');
		if ($ns)
		{
			$container = new \Zend\Session\Container($ns);
		}
		else
		{
			$container = change_Controller::getInstance()->getStorage()->getUserSessionContainer();
		}
		return $container->getArrayCopy();
	}

	/**
	 * @deprecated
	 */
	public function getAttributeNamespaces()
	{
		Framework::deprecated('Call to deprecated method ' . get_class($this) . '->getAttributeNamespaces');
		return array();
	}

	/**
	 * @deprecated
	 */
	public function hasAttributeNamespace($ns)
	{
		Framework::deprecated('Call to deprecated method ' . get_class($this) . '->hasAttributeNamespace');
		return false;
	}
	
	/**
	 * @deprecated
	 */
	public function removeAttributeNamespace($ns)
	{
		Framework::deprecated('Call to deprecated method ' . get_class($this) . '->removeAttributeNamespace');
	}
}
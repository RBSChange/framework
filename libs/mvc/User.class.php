<?php
class change_User
{
	const BACKEND_NAMESPACE = 'backend';
	const FRONTEND_NAMESPACE = 'frontend';
	
	const AUTH_NAMESPACE = 'User/authenticated';

	// extends ParameterHolder
	const ATTRIBUTE_NAMESPACE = 'User/attributes';

	/**
	 * @var straing
	 */
	private $userNamespace = self::FRONTEND_NAMESPACE;
	
	
	/**
	 * @var array
	 */
	private $attributes = null;
	
	/**
	 * @var change_Context
	 */
	private $context = null;

	/**
	 * @var array
	 */
	private $authenticated;
	
	/**
	 * @return change_Context
	 */
	public function getContext()
	{
		return $this->context;
	}
	
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
	
	public function initialize($context, $parameters = null)
	{	
		$this->context = $context;
	}
	
	public function shutdown()
	{	
		
	}
	
	/**
	 *@deprecated
	 */
	protected function checkLoadedNamespace($ns = null)
	{
		// No implementation
		return false;
	}
	
	/**
	 * @param string $userNamespace
	 * @return string Old namespace
	 */
	public function setUserNamespace($userNamespace)
	{
		if ($userNamespace !== self::BACKEND_NAMESPACE)
		{
			$userNamespace = self::FRONTEND_NAMESPACE;
		}
		
		$oldNamespace = $this->userNamespace;
		$this->userNamespace = $userNamespace;
		return $oldNamespace;
	}
	
	/**
	 *
	 * @return string 
	 */
	public function getUserNamespace()
	{
		return $this->userNamespace;
	}

	/**
	 * @return string
	 */
	public function getLogin()
	{
		return change_Controller::getInstance()->getStorage()->readForUser('framework_login'); 
	}
	
	/**
	 * @param string $login
	 */
	public function setLogin($login)
	{
		change_Controller::getInstance()->getStorage()->writeForUser('framework_login', $login); 
	}
	
	/**
	 * @return integer
	 */
	public function getId()
	{
		return change_Controller::getInstance()->getStorage()->readForUser('framework_userid');
	}
	
	/**
	 * @param integer $id
	 */
	public function setId($id)
	{
		change_Controller::getInstance()->getStorage()->writeForUser('framework_userid', $id);
	}
	
	/**
	 * Initializes the FrameworkSecurityUser using a modules_users/user.
	 * @param users_persistentdocument_user $user
	 */
	public function setUser($user)
	{
		if ($user instanceof users_persistentdocument_user) 
		{
			$isRoot = $user->getIsroot();
			change_Controller::getInstance()->getStorage()->writeForUser('framework_isRoot', $isRoot);
			$this->setLogin($user->getLogin());
			$this->setId($user->getId());
		}
	}
	
	/**
	 * Get the superuser attribute for the user. 
	 *
	 * @return boolean true if super user false otherwise.
	 */
	public function isRoot()
	{
		return change_Controller::getInstance()->getStorage()->readForUser('framework_isRoot') === true;
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
			$sessionContainer = change_Controller::getInstance()->getStorage()->getUserSessionNamespaceInstance();
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
			$sessionContainer = change_Controller::getInstance()->getStorage()->getUserSessionNamespaceInstance();
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
			$sessionContainer = change_Controller::getInstance()->getStorage()->getUserSessionNamespaceInstance();
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
			$sessionContainer = change_Controller::getInstance()->getStorage()->getUserSessionNamespaceInstance();
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
			$sessionContainer = change_Controller::getInstance()->getStorage()->getUserSessionNamespaceInstance();
		}
		foreach ($attributes as $name => $value)
		{
			change_Controller::getInstance()->getStorage()->writeForContainer($name, $value, $sessionContainer);
		}
	}
		
	/**
	 * @return Booolean
	 */
	public function isAuthenticated()
	{
		$data = change_Controller::getInstance()->getStorage()->readForUser('framework_isAuthenticated');
		return $data == true;
	}
	
	/**
	 * @param boolean $authenticated
	 */
	public function setAuthenticated($authenticated)
	{
		if ($authenticated === true)
		{
			change_Controller::getInstance()->getStorage()->writeForUser('framework_isAuthenticated', $authenticated);
		}
		else
		{
			change_Controller::getInstance()->getStorage()->removeForUser('framework_isAuthenticated');
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
			$container = change_Controller::getInstance()->getStorage()->getUserSessionNamespaceInstance();
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
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
	 * @param String $class
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
	 * @param String $userNamespace
	 * @return String Old namespace
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
	 * @return String 
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
	 *	
	 * @param users_persistentdocument_user $user
	 * @throws IllegalArgumentException
	 */
	public function setUser($user)
	{
		
		if ($user instanceof users_persistentdocument_backenduser)
		{
			$isRoot = $user->getIsroot();
		}
		else
		{
			$isRoot = false;
		}
		
		change_Controller::getInstance()->getStorage()->writeForUser('framework_isRoot', $isRoot);
		$this->setLogin($user->getLogin());
		$this->setId($user->getId());
	}
	
	/**
	 * Get the superuser attribute for the user. 
	 *
	 * @return Boolean true if super user false otherwise.
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
			$zns = new Zend_Session_Namespace($ns);
			return change_Controller::getInstance()->getStorage()->readNS($name, $zns);
		}
		return change_Controller::getInstance()->getStorage()->readForUser($name);
	}
	
	/**
	 * @deprecated
	 */
	public function removeAttribute($name, $ns = null)
	{
		Framework::error('Call to deprecated method ' . get_class($this) . '->removeAttribute');
		$retval = null;
		if ($ns)
		{
			$zns = new Zend_Session_Namespace($ns);
			$retval = change_Controller::getInstance()->getStorage()->readNS($name, $zns);
			change_Controller::getInstance()->getStorage()->removeNS($name, $zns);
		}
		else
		{
			$retval = change_Controller::getInstance()->getStorage()->readForUser($name, $zns);
			change_Controller::getInstance()->getStorage()->removeForUser($name);
		}
		return $retval;
	}
		
	/**
	 * @deprecated
	 */
	public function getAttribute($name, $ns = null)
	{
		Framework::error('Call to deprecated method ' . get_class($this) . '->getAttribute');
		if ($ns)
		{
			$zns = new Zend_Session_Namespace($ns);
			return change_Controller::getInstance()->getStorage()->readNS($name, $zns);
		}
		return change_Controller::getInstance()->getStorage()->readForUser($name, $zns);
	}
	
	/**
	 * @deprecated
	 */
	public function getAttributeNames($ns = null)
	{
		Framework::error('Call to deprecated method ' . get_class($this) . '->getAttributeNames');
		if ($ns)
		{
			$zns = new Zend_Session_Namespace($ns);
		}
		else
		{
			$zns = change_Controller::getInstance()->getStorage()->getUserSessionNamespaceInstance();
		}
		return array_keys($zns->getIterator());
	}
		
	/**
	 * @deprecated
	 */
	public function setAttribute($name, $value, $ns = null)
	{
		Framework::error('Call to deprecated method ' . get_class($this) . '->setAttribute');
		if ($ns)
		{
			$zns = new Zend_Session_Namespace($ns);
		}
		else
		{
			$zns = change_Controller::getInstance()->getStorage()->getUserSessionNamespaceInstance();
		}
		change_Controller::getInstance()->getStorage()->writeNS($name, $value, $zns);
	}

	/**
	 * @deprecated
	 */
	public function setAttributeByRef($name, &$value, $ns = null)
	{
		Framework::error('Call to deprecated method ' . get_class($this) . '->setAttributeByRef');
		if ($ns)
		{
			$zns = new Zend_Session_Namespace($ns);
		}
		else
		{
			$zns = change_Controller::getInstance()->getStorage()->getUserSessionNamespaceInstance();
		}
		change_Controller::getInstance()->getStorage()->writeNS($name, $value, $zns);
	}

	/**
	 * @deprecated
	 */
	public function setAttributes($attributes, $ns = null)
	{
		Framework::error('Call to deprecated method ' . get_class($this) . '->setAttributes');
		if ($ns)
		{
			$zns = new Zend_Session_Namespace($ns);
		}
		else
		{
			$zns = change_Controller::getInstance()->getStorage()->getUserSessionNamespaceInstance();
		}
		foreach ($attributes as $name => $value)
		{
			change_Controller::getInstance()->getStorage()->writeNS($name, $value, $zns);
		}
	}
	
	/**
	 * @deprecated
	 */
	public function setAttributesByRef (&$attributes, $ns = null)
	{
		Framework::error('Call to deprecated method ' . get_class($this) . '->setAttributesByRef');
		if ($ns)
		{
			$zns = new Zend_Session_Namespace($ns);
		}
		else
		{
			$zns = change_Controller::getInstance()->getStorage()->getUserSessionNamespaceInstance();
		}
		foreach ($attributes as $name => $value)
		{
			change_Controller::getInstance()->getStorage()->writeNS($name, $value, $zns);
		}
	}
		
	/**
	 * @return Booolean
	 */
	public function isAuthenticated()
	{
		return change_Controller::getInstance()->getStorage()->readForUser('framework_isAuthenticated') == true;
	}
	
	/**
	 * @param Boolean $authenticated
	 */
	public function setAuthenticated($authenticated)
	{
		if ($authenticated === true)
		{
			return change_Controller::getInstance()->getStorage()->writeForUser('framework_isAuthenticated', $authenticated);
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
		Framework::error('Call to deprecated method ' . get_class($this) . '->getAttributeNamespace');
		if ($ns)
		{
			$zns = new Zend_Session_Namespace($ns);
		}
		else
		{
			$zns = change_Controller::getInstance()->getStorage()->getUserSessionNamespaceInstance();
		}
		return $zns->getIterator()->getArrayCopy();
	}

	/**
	 * @deprecated
	 */
	public function getAttributeNamespaces()
	{
		return Zend_Session::getIterator()->getIterator();
	}

	/**
	 * @deprecated
	 */
	public function hasAttributeNamespace($ns)
	{
		return Zend_Session::namespaceIsset($ns);
	}
	
	/**
	 * @deprecated
	 */
	public function removeAttributeNamespace($ns)
	{
		return Zend_Session::namespaceUnset($ns);
	}
}
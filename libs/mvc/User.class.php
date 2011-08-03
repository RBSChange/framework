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
	
	public static function newInstance($class)
	{
		$object = new $class();
		if (!($object instanceof change_User))
		{
			$error = 'Class "'.$class.'" is not of the type FrameworkSecurityUser';
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
		if ($this->attributes !== null)
		{	
			$storage = $this->getContext()->getStorage();
			$storage->write(self::AUTH_NAMESPACE, $this->authenticated);
			$storage->write(self::ATTRIBUTE_NAMESPACE, $this->attributes);
		}
	}
	
	protected function checkLoadedNamespace($ns = null)
	{
		if ($this->attributes === null)
		{
			$storage = $this->getContext()->getStorage();
			$this->attributes = $storage->read(self::ATTRIBUTE_NAMESPACE);
			if ($this->attributes == null)
			{
				$this->attributes = array();
			}
			
			$this->authenticated = $storage->read(self::AUTH_NAMESPACE);
			if ($this->authenticated == null)
			{
				$this->authenticated = array();
				$storage->write(self::AUTH_NAMESPACE, $this->authenticated);
			}
		}
		
		return $ns === null ? $this->userNamespace : $ns;
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
	 * @return string
	 */
	public function getLogin()
	{
		return $this->getAttribute('login', $this->userNamespace);
	}
	
	/**
	 * @param string $login
	 */
	public function setLogin($login)
	{
		$this->setAttribute('login', $login, $this->userNamespace);
	}
	
	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->getAttribute('userid', $this->userNamespace);
	}
	
	/**
	 * @param integer $id
	 */
	public function setId($id)
	{
		$this->setAttribute('userid', $id, $this->userNamespace);
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
			$this->setAttribute('isRoot', $user->getIsroot(), $this->userNamespace);
		}
		else if ($user instanceof users_persistentdocument_frontenduser)
		{
			$this->setAttribute('isRoot', false, $this->userNamespace);
		}
		else
		{
			$this->setAttribute('isRoot', false, $this->userNamespace);
		}
		
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
		return $this->getAttribute('isRoot', $this->userNamespace) === true;
	}
	
	/**
	 * @return void
	 */
	public function clearAttributes()
	{
		$namespaces = $this->getAttributeNamespaces();
		if ($this->userNamespace === self::BACKEND_NAMESPACE)
		{
			$reserved = self::FRONTEND_NAMESPACE;
		}
		else
		{
			$reserved = self::BACKEND_NAMESPACE;
		}
		
		foreach ($namespaces as $namespace)
		{
			if ($namespace == $reserved)
			{
				continue;
			}
			$this->removeAttributeNamespace($namespace);
		}
	}
	
	/**
	 * @param String $name
	 * @param String $ns
	 * @return String
	 */
	public function hasAttribute($name, $ns = null)
	{
		$ns = $this->checkLoadedNamespace($ns);	
		return (isset($this->attributes[$ns])) && isset($this->attributes[$ns][$name]);
	}
	
	/**
	 * @param String $name
	 * @param String $ns
	 * @return Mixed
	 */
	public function removeAttribute($name, $ns = null)
	{
		$ns = $this->checkLoadedNamespace($ns);	
		
		$retval = null;
		if (isset($this->attributes[$ns]) && isset($this->attributes[$ns][$name]))
		{
			$retval = $this->attributes[$ns][$name];
			unset($this->attributes[$ns][$name]);
		}
		return $retval;
	}
		
	/**
	 * @param String $name
	 * @param String $ns
	 * @return mixed
	 */
	public function getAttribute($name, $ns = null)
	{
		$ns = $this->checkLoadedNamespace($ns);	
		
		$retval = null;
		if (isset($this->attributes[$ns]) && isset($this->attributes[$ns][$name]))
		{
			return $this->attributes[$ns][$name];
		}
		return $retval;
	}
	
	/**
	 * @param String $ns
	 * @return Array
	 */
	public function getAttributeNames($ns = null)
	{
		$ns = $this->checkLoadedNamespace($ns);			
		if (isset($this->attributes[$ns]))
		{
			return array_keys($this->attributes[$ns]);
		}
		return array();
	}
		
	/**
	 * @param String $name
	 * @param mixed $value
	 * @param String $ns
	 */
	public function setAttribute($name, $value, $ns = null)
	{
		$ns = $this->checkLoadedNamespace($ns);
		if (!isset($this->attributes[$ns]))
		{
			$this->attributes[$ns] = array();
		}
		$this->attributes[$ns][$name] = $value;
	}

	/**
	 * Set an attribute by reference.
	 *
	 * @param String $name
	 * @param mixed $value
	 * @param String $ns
	 */
	public function setAttributeByRef($name, &$value, $ns = null)
	{
		$ns = $this->checkLoadedNamespace($ns);
		if (!isset($this->attributes[$ns]))
		{
			$this->attributes[$ns] = array();
		}
		$this->attributes[$ns][$name] = &$value;
	}

	/**
	 * Set an array of attributes.
	 * If an existing attribute name matches any of the keys in the supplied
	 * array, the associated value will be overridden.
	 *
	 * @param Array $attributes
	 * @param String $ns
	 */
	public function setAttributes($attributes, $ns = null)
	{
		$ns = $this->checkLoadedNamespace($ns);
		if (!isset($this->attributes[$ns]))
		{
			$this->attributes[$ns] = array();
		}
		$this->attributes[$ns] = array_merge($this->attributes[$ns], $attributes);
	}
	
	/**
	 * Set an array of attributes by reference.
	 * If an existing attribute name matches any of the keys in the supplied
	 * array, the associated value will be overridden.
	 *
	 * @param Array $attributes
	 * @param String $ns
	 */
	public function setAttributesByRef (&$attributes, $ns = null)
	{
		$ns = $this->checkLoadedNamespace($ns);
		if (!isset($this->attributes[$ns]))
		{
			$this->attributes[$ns] = array();
		}
		foreach ($attributes as $key => &$value)
		{
			$this->attributes[$ns][$key] = &$value;
		}
	}
		
	/**
	 * @return Booolean
	 */
	public function isAuthenticated()
	{
		$ns = $this->checkLoadedNamespace();
		return isset($this->authenticated[$ns]) && $this->authenticated[$ns];
	}
	
	/**
	 * @param Boolean $authenticated
	 */
	public function setAuthenticated($authenticated)
	{
		$ns = $this->checkLoadedNamespace();
		if ($authenticated === true)
		{
			$this->authenticated[$ns] = true;
		}
		else if (isset($this->authenticated[$ns]))
		{
			unset($this->authenticated[$ns]);
		}
	}
	
	public function getAttributeNamespace($ns = null)
	{
		$ns = $this->checkLoadedNamespace($ns);
		$retval = null;
		if (isset($this->attributes[$ns]))
		{
			return $this->attributes[$ns];
		}
		return $retval;
	}

	public function getAttributeNamespaces()
	{
		$this->checkLoadedNamespace();
		return array_keys($this->attributes);
	}


	public function hasAttributeNamespace($ns)
	{
		$ns = $this->checkLoadedNamespace($ns);
		return isset($this->attributes[$ns]);
	}

	public function removeAttributeNamespace($ns)
	{
		$ns = $this->checkLoadedNamespace($ns);
		if (isset($this->attributes[$ns]))
		{
			unset($this->attributes[$ns]);
		}
	}
}
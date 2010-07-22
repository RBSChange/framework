<?php
/**
 * FrameworkSecurityUser
 * 
 * @package    change
 * @subpackage user
 */
class FrameworkSecurityUser extends SecurityUser
{
	const BACKEND_NAMESPACE = 'backend';
	const FRONTEND_NAMESPACE = 'frontend';
	
	const AUTH_NAMESPACE = 'org/agavi/user/BasicSecurityUser/authenticated';
	const CREDENTIAL_NAMESPACE = 'org/agavi/user/BasicSecurityUser/credentials';
	
	/**
	 * @var straing
	 */
	private $userNamespace = self::FRONTEND_NAMESPACE;

	/**
	 * @var array
	 */
	private $authenticated;
	
	/**
	 * @var array
	 */
	private $credentials;
	
	
	public function initialize($context, $parameters = null)
	{	
		// initialize parent
		parent::initialize($context, $parameters);
		
		$storage = $this->getContext()->getStorage();
		$this->authenticated = $storage->read(self::AUTH_NAMESPACE);
		$this->credentials = $storage->read(self::CREDENTIAL_NAMESPACE);
		
		if ($this->authenticated == null)
		{
			$this->authenticated = array();
			$this->credentials = array();
		}
	}
	
	public function shutdown()
	{
		
		$storage = $this->getContext()->getStorage();
		// write credentials to the storage
		$storage->write(self::AUTH_NAMESPACE, $this->authenticated);
		$storage->write(self::CREDENTIAL_NAMESPACE, $this->credentials);
		
		// call the parent shutdown method
		parent::shutdown();
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
		return parent::getAttribute('login', $this->userNamespace);
	}
	
	/**
	 * @param string $login
	 */
	public function setLogin($login)
	{
		parent::setAttribute('login', $login, $this->userNamespace);
	}
	
	/**
	 * @return integer
	 */
	public function getId()
	{
		return parent::getAttribute('userid', $this->userNamespace);
	}
	
	/**
	 * @param integer $id
	 */
	public function setId($id)
	{
		parent::setAttribute('userid', $id, $this->userNamespace);
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
			parent::setAttribute('isRoot', $user->getIsroot(), $this->userNamespace);
		}
		else if ($user instanceof users_persistentdocument_frontenduser)
		{
			parent::setAttribute('isRoot', false, $this->userNamespace);
		}
		else
		{
			parent::setAttribute('isRoot', false, $this->userNamespace);
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
		return parent::getAttribute('isRoot', $this->userNamespace) === true;
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
		if ($ns === null)
		{
			$ns = $this->userNamespace;
		}
		return parent::hasAttribute($name, $ns);
	}
	
	/**
	 * @param String $name
	 * @param String $ns
	 * @return Mixed
	 */
	public function &removeAttribute($name, $ns = null)
	{
		if ($ns === null)
		{
			$ns = $this->userNamespace;
		}
		return parent::removeAttribute($name, $ns);
	}
		
	/**
	 * @param String $name
	 * @param String $ns
	 * @return mixed
	 */
	public function &getAttribute($name, $ns = null)
	{
		if ($ns === null)
		{
			$ns = $this->userNamespace;
		}
		return parent::getAttribute($name, $ns);
	}
	
	/**
	 * @param String $ns
	 * @return Array
	 */
	public function getAttributeNames($ns = null)
	{
		if ($ns === null)
		{
			$ns = $this->userNamespace;
		}
		return parent::getAttributeNames($ns);
	}
		
	/**
	 * @param String $name
	 * @param mixed $value
	 * @param String $ns
	 */
	public function setAttribute($name, $value, $ns = null)
	{
		if ($ns === null)
		{
			$ns = $this->userNamespace;
		}
		parent::setAttribute($name, $value, $ns);
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
		if ($ns === null)
		{
			$ns = $this->userNamespace;
		}
		parent::setAttributeByRef($name, $value, $ns);
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
		if ($ns === null)
		{
			$ns = $this->userNamespace;
		}
		parent::setAttributes($attributes, $ns);
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
		if ($ns === null)
		{
			$ns = $this->userNamespace;
		}
		parent::setAttributesByRef($attributes, $ns);
	}
		
	// Credential handling.
	// FIXME: are this methods used any more or do they should be deleted?

	public function addCredential($credential)
	{
		if (! isset($this->credentials[$this->userNamespace]))
		{
			$this->credentials[$this->userNamespace] = array();
		}
		
		if (! in_array($credential, $this->credentials[$this->userNamespace]))
		{
			
			$this->credentials[$this->userNamespace][] = $credential;
		}
	}
	
	public function clearCredentials()
	{
		if (isset($this->credentials[$this->userNamespace]))
		{
			unset($this->credentials[$this->userNamespace]);
		}
	}
	
	public function hasCredential($credential)
	{
		if (isset($this->credentials[$this->userNamespace]))
		{
			return (in_array($credential, $this->credentials[$this->userNamespace]));
		}
		return false;
	}
	
	public function removeCredential($credential)
	{
		if ($this->hasCredential($credential))
		{
			$count = count($this->credentials[$this->userNamespace]);
			for($i = 0; $i < $count; $i ++)
			{
				if ($credential == $this->credentials[$this->userNamespace][$i])
				{
					unset($this->credentials[$this->userNamespace][$i]);
					break;
				}
			}
		}
	}

	/**
	 * @return Booolean
	 */
	public function isAuthenticated()
	{
		return isset($this->authenticated[$this->userNamespace]) && $this->authenticated[$this->userNamespace];
	}
	
	/**
	 * @param Boolean $authenticated
	 */
	public function setAuthenticated($authenticated)
	{
		if ($authenticated === true)
		{
			$this->authenticated[$this->userNamespace] = true;
		}
		else if (isset($this->authenticated[$this->userNamespace]))
		{
			unset($this->authenticated[$this->userNamespace]);
			$this->clearCredentials();
		}
	}
}

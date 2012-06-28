<?php
class change_Storage
{
	
	/**
	 * @var change_Context
	 */
	private $context = null;
	
	/**
	 *
	 * @var Zend_Session_Namespace 
	 */
	private $changeSessionNameSpace;
	
	/**
	 *
	 * @var Zend_Session_Namespace 
	 */
	private $backuserSessionNameSpace;
	
	/**
	 *
	 * @var Zend_Session_Namespace 
	 */
	private $frontuserSessionNameSpace;
	
	/**
	 * @var array
	 */
	protected $parameters = array('session_name' => '__CHANGESESSIONID', 'auto_start' => true);
	
	private $started = null;
	
	/**
	 * @param change_Context $context
	 * @param array $parameters
	 */
	public function initialize ($context, $parameters = null)
	{
		$this->context = $context;
		if ($parameters != null)
		{
			$this->parameters = array_merge($this->parameters, $parameters);
		}	
	
		if (!$this->parameters['auto_start'])
		{
			$this->startSession();
		}
	}
	
	protected function startSession()
	{
		if (isset($_SERVER["SERVER_ADDR"]))
		{
			session_name($this->parameters['session_name']);
			Zend_Session::start();
			
			$this->changeSessionNameSpace = new Zend_Session_Namespace('GLOBAL');
			$this->backuserSessionNameSpace = new Zend_Session_Namespace('BACKOFFICE');
			$this->frontuserSessionNameSpace = new Zend_Session_Namespace('FRONTOFFICE');
			$this->started = true;
			Framework::sessionStarted(Zend_Session::getId());
	
			$currentKey =  $this->getSecureKey(); 
			$md5 = $this->read('framework_SecureKey');
			if ($md5 === null)
			{
				$this->write('framework_SecureKey', $currentKey);
				$this->write('framework_SecurePort', $_SERVER["SERVER_PORT"]);
				
			} 
			else if ($md5 !== $currentKey)
			{
				$oldSessionId = Zend_Session::getId();
				Zend_Session::destroy();
				Zend_Session::start();		
				Framework::sessionStarted(Zend_Session::getId());		
				$this->sessionIdChanged($oldSessionId);
				
			}
			else if ($this->read('framework_SecurePort') !== $_SERVER["SERVER_PORT"])
			{
				$oldSessionId = Zend_Session::getId();
				Zend_Session::regenerateId();
				$this->write('framework_SecurePort', $_SERVER["SERVER_PORT"]);
				Framework::sessionStarted(Zend_Session::getId());
				$this->sessionIdChanged($oldSessionId);	
			}
		}
		else
		{
			$this->started = false;
		}
	}
	
	protected function stopSession()
	{
		if ($this->started === true)
		{
			$this->started = null;
			Zend_Session::stop();
		}
	}

	/**
	 * @return string
	 */
	protected function getSecureKey()
	{
		$string = 'CHANGEKEY';
		if (defined('SECURE_SESSION_BY_IP') &&  SECURE_SESSION_BY_IP)
		{
			$string .= $_SERVER['REMOTE_ADDR'];
		}
		return md5($string);
	}
	
	/**
	 * @param String $oldSessionId 
	 */
	protected function sessionIdChanged($oldSessionId)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' Old Id:' . $oldSessionId);
		}		
	}

	/**
	 * @return change_Context
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * @param string $class
	 * @return change_Storage 
	 */
	public static function newInstance($class)
	{
		$object = new $class();
		if (!($object instanceof change_Storage))
		{
			$error = 'Class "' .$class .'" is not of the type change_Storage';
			throw new Exception($error);
		}
		return $object;
	}
	
	/**
	 * @param String $key
	 * @param Zend_Session_Namespace $ns
	 * @return Mixed 
	 */
	public function &readNS($key, $ns)
	{
		if ($this->started === null) {$this->startSession();}
		$retval = null;
		if ($this->started && isset($ns->$key))
		{
			$retval =  $ns->$key;	
		}
		return $retval;
	}
	
	/**
	 * @param String $key
	 * @return String 
	 */
	public function &read($key)
	{
		return $this->readNS($key, $this->getChangeSessionNamespaceInstance());
	}
	
	/**
	 * @param String $key
	 * @return String 
	 */
	public function &readForUser($key)
	{
		return $this->readNS($key, $this->getUserSessionNamespaceInstance());
	}
	
	public function removeNS($key, $ns)
	{
		if ($this->started === null) {$this->startSession();}
		
		$retval = null;
		if ($this->started && isset($ns->$key))
		{
			$retval = $ns->$key;
			unset($ns->$key);
		}
		return $retval;
	}
	
	/**
	 * @param String $key
	 * @return Mixed
	 */
	public function remove($key)
	{
		return $this->removeNS($key, $this->getChangeSessionNamespaceInstance());
	}
	
	/**
	 * @param String $key
	 * @return Mixed
	 */
	public function removeForUser($key)
	{
		return $this->removeNS($key, $this->getUserSessionNamespaceInstance());
	}


	/**
	 * @param String $key
	 * @param Mixed $data
	 * @param Zend_Session_Namespace $ns 
	 */
	public function writeNS($key, &$data, $ns)
	{
		if ($this->started === null) {$this->startSession();}
		if ($this->started)
		{
			$ns->$key = $data;
		}
	}
	
	/**
	 * @param String $key
	 * @param Mixed $data
	 */
	public function write($key, &$data)
	{
		$this->writeNS($key, $data, $this->getChangeSessionNamespaceInstance());
	}
	
	/**
	 * @param String $key
	 * @param Mixed $data
	 */
	public function writeForUser($key, &$data)
	{
		$this->writeNS($key, $data, $this->getUserSessionNamespaceInstance());
	}
	
	/**
	 * @param Zend_Session_Namespace $ns
	 * @return Array 
	 */
	public function readAllNS($ns)
	{
		if ($this->started === null) {$this->startSession();}
		if ($this->started)
		{
			return $ns->getIterator()->getArrayCopy();
		}
		return array();
	}
	
	/**
	 * @return Array 
	 */
	public function readAll()
	{
		return $this->readAllNS($this->getChangeSessionNamespaceInstance());
	}
		
	/**
	 * @return Array 
	 */
	public function readAllForUser()
	{
		return $this->readAllNS($this->getUserSessionNamespaceInstance());
	}
	
	public function clear()
	{
		$ns = $this->getChangeSessionNamespaceInstance();
		if ($this->started) {$ns->unsetAll();}
	}
	
	public function clearForUser()
	{
		$ns = $this->getUserSessionNamespaceInstance();
		if ($this->started) {$ns->unsetAll();}
	}
	
	public function shutdown ()
	{
		$this->stopSession();
	}
	
	/**
	 * This method returns the Zend_Session_Namespace instance used to store related session data
	 * 
	 * @return Zend_Session_Namespace 
	 */
	public function getChangeSessionNamespaceInstance()
	{
		if ($this->started === null) {$this->startSession();}
		return $this->changeSessionNameSpace;
	}
	
	/**
	 * This method returns the Zend_Session_Namespace instance used to store session data whose scope is authentified navigation only (ie: gets cleaned when authentified user disconnects)
	 * 
	 * @return Zend_Session_Namespace 
	 */
	public function getUserSessionNamespaceInstance()
	{
		if ($this->context->getUser()->getUserNamespace() === change_User::BACKEND_NAMESPACE)
		{
			return $this->getBackofficeSessionNamespaceInstance();
		}
		return $this->getFrontofficeSessionNamespaceInstance();
	}
	
	/**
	 * @return Zend_Session_Namespace 
	 */
	public function getBackofficeSessionNamespaceInstance()
	{
		if ($this->started === null) {$this->startSession();}
		return $this->backuserSessionNameSpace;	
	}
	
	/**
	 * @return Zend_Session_Namespace 
	 */
	public function getFrontofficeSessionNamespaceInstance()
	{
		if ($this->started === null) {$this->startSession();}
		return $this->frontuserSessionNameSpace;
	}
}
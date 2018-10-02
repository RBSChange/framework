<?php
abstract class Storage
{
	/**
	 * @var Context
	 */
	private $context = null;
	
	/**
	 * @var array
	 */
	protected $parameters = array();

	public function clearParameters()
	{
		$this->parameters = null;
		$this->parameters = array();
	}

	public function getParameter($name, $default = null)
	{
		return (isset($this->parameters[$name])) ? $this->parameters[$name] : $default;
	}

	public function getParameterNames()
	{
		return array_keys($this->parameters);
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	public function hasParameter($name)
	{
		return isset($this->parameters[$name]);

	}

	public function removeParameter($name)
	{
		if (isset($this->parameters[$name]))
		{
			$retval = $this->parameters[$name];
			unset($this->parameters[$name]);
			return $retval;
		}
		return null;
	}

	public function setParameter($name, $value)
	{
		$this->parameters[$name] = $value;
	}

	public function setParameterByRef($name, &$value)
	{
		$this->parameters[$name] = &$value;
	}

	public function setParameters ($parameters)
	{
		$this->parameters = array_merge($this->parameters, $parameters);
	}

	public function setParametersByRef(&$parameters)
	{
		foreach ($parameters as $key => &$value)
		{
			$this->parameters[$key] = &$value;
		}
	}

	public function getContext()
	{
		return $this->context;
	}

	public function initialize ($context, $parameters = null)
	{
		$this->context = $context;
		if ($parameters != null)
		{
			$this->parameters = array_merge($this->parameters, $parameters);
		}
	}

	public static function newInstance ($class)
	{
		$object = new $class();
		if (!($object instanceof Storage))
		{

			$error = 'Class "%s" is not of the type Storage';
			$error = sprintf($error, $class);
			throw new Exception($error);
		}
		return $object;
	}

	abstract function &read ($key);

	abstract function remove ($key);

	abstract function shutdown ();

	abstract function write ($key, &$data);
}

class SessionStorage extends Storage
{
	private $started = false;
	
	public function initialize ($context, $parameters = null)
	{
		parent::initialize($context, $parameters);
		$sessionName = $this->getParameter('session_name', '__CHANGESESSIONID');
		if (!session_name()) {
			session_name($sessionName);
		}
		if ($this->getParameter('auto_start', true))
		{
			if (session_id() == "")
			{
				session_start();
			}
			$this->started = true;
		}
	}

	public function &read($key)
	{
		$retval = null;
		if ($this->started && isset($_SESSION[$key]))
		{
			$retval = &$_SESSION[$key];
		}
		return $retval;
	}

	public function remove($key)
	{
		$retval = null;
		if ($this->started && isset($_SESSION[$key]))
		{
			$retval = $_SESSION[$key];
			unset($_SESSION[$key]);
		}
		return $retval;
	}

	public function shutdown ()
	{
		if ($this->started)
		{
			$this->started = false;
			session_write_close();
		}
	}

	public function write($key, &$data)
	{
		if ($this->started)
		{
			$_SESSION[$key] = &$data;
		}
	}
}

class ChangeSessionStorage extends SessionStorage
{
	public function initialize ($context, $parameters = null)
	{
		if (isset($_SERVER["SERVER_ADDR"]))
		{
			$this->setParameter('session_name', '__CHANGESESSIONID');
			$currentKey =  $this->getSecureKey(); 
			parent::initialize($context, $parameters);
			$md5 = $this->read('SecureKey');
			if ($md5 === null)
			{
				$this->write('SecureKey', $currentKey);
				$this->write('SecurePort', $_SERVER["SERVER_PORT"]);
			} 
			else if ($md5 !== $currentKey)
			{
				$oldSessionId = session_id();
				session_regenerate_id();
				$_SESSION = array();
				$this->sessionIdChanged($oldSessionId);
			}
			else if ($this->read('SecurePort') !== $_SERVER["SERVER_PORT"])
			{
				$oldSessionId = session_id();
				session_regenerate_id();
				$this->write('SecurePort', $_SERVER["SERVER_PORT"]);
				$this->sessionIdChanged($oldSessionId);	
			}
		}
		else
		{
			$this->setParameter('auto_start', false);
			parent::initialize($context, $parameters);
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
	
	protected function sessionIdChanged($oldSessionId)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' ' . $oldSessionId . ' -> ' . session_id());
		}		
	}
}

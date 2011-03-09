<?php
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
				session_regenerate_id();
				$_SESSION = array();
			}
			else if ($this->read('SecurePort') !== $_SERVER["SERVER_PORT"])
			{
				session_regenerate_id();
				$this->write('SecurePort', $_SERVER["SERVER_PORT"]);	
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
	private function getSecureKey()
	{
		$string = 'CHANGE';
		if (defined('SECURE_SESSION_BY_IP') &&  SECURE_SESSION_BY_IP)
		{
			$string .= $_SERVER['REMOTE_ADDR'];
		}
		return md5($string);
	}
}

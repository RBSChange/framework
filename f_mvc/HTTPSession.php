<?php
class f_mvc_HTTPSession implements f_mvc_Session 
{
	/**
	 * @param String $name
	 * @param mixed $value
	 */
	function setAttribute($name, $value)
	{
		$_SESSION[$name] = $value;
	}

	/**
	 * @param String $name
	 * @return mixed
	 */
	function getAttribute($name)
	{
		if (isset($_SESSION[$name]))
		{
			return $_SESSION[$name];
		}
		return null;
	}

	/**
	 * @param String $name
	 */
	function removeAttribute($name)
	{
		unset($_SESSION[$name]);
	}
	

	/**
	 * @return array<String, mixed>
	 */
	function getAttributes()
	{
		return $_SESSION;
	}

	/**
	 * @param String $name
	 * @return Boolean
	 */
	function hasAttribute($name)
	{
		return array_key_exists($name, $_SESSION);
	}

}
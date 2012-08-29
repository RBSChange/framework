<?php
class f_event_Event
{
	private $name;
	private $sender;
	private $params;
	
	/**
	 * @param string $name the event name (aka identifier)
	 * @param Mixed $sender the source of the event
	 * @param Array $params the parameters as a (paramName => paramValue) array, optional
	 */
	function __construct($name, $sender, $params = null)
	{
		$this->name = $name;
		$this->sender = $sender;
		$this->params = $params;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getSender()
	{
		return $this->sender;
	}
	
	/**
	 * @param string $paramName
	 * @return Mixed param value or null
	 */
	
	public function getParam($paramName)
	{
		if (is_null($this->params))
		{
			return null;
		}
		return $this->params[$paramName];
	}
}
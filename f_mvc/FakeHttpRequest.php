<?php
class f_mvc_FakeHttpRequest extends f_mvc_HTTPRequest 
{
	private $parameters;
	
	/**
	 * @var f_mvc_HTTPRequest
	 */
	private $httpRequest;
	
	function __construct($parametersArray = array())
	{
		if (!is_array($parametersArray))
		{
			throw new IllegalArgumentException(__METHOD__ . ' is expecting an array');
		}
		$this->parameters = $parametersArray;
		$this->httpRequest = f_mvc_HTTPRequest::getInstance();
	}
		
	/**
	 * 
	 * @param String $name 
	 * @param String $defaultValue 
	 * @return String the value of the parameter or $defaultValue 
	 * @see f_mvc_Request::getParameter()
	 */
	function getParameter($name, $defaultValue = null)
	{
		if (isset($this->parameters[$name]))
		{
			return $this->parameters[$name];
		}
		return $defaultValue;
	}
	
	/**
	 * 
	 * @return array<String, array<String>> 
	 * @see f_mvc_Request::getParameters()
	 */
	function getParameters()
	{
		return $this->parameters;

	}

	/**
	 * @param String $name 
	 * @return Boolean 
	 * @see f_mvc_Request::hasParameter()
	 */
	function hasParameter($name)
	{
		return isset($this->parameters[$name]);
	}
}
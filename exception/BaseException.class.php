<?php
class BaseException extends Exception
{
	private $attributes ;
	private $key;
	
	public function __construct($message, $key = null, $attributes = array())
	{
		$this->attributes = $attributes;
		$this->key = $key;
		parent::__construct($message);
	}
	
	/**
	 * @return string
	 */
	public final function getKey()
	{
		return $this->key;
	}
	
	/**
	 * @param string $key
	 */
	public final function setKey($key)
	{
		$this->key = $key;
	}
	
	public function getAttributes()
	{
		return $this->attributes;
	}
	
	function setAttribute($name,$value)
	{
		$this->attributes[$name]=$value;
		return $this;
	}

	function getAttribute($name)
	{
		return $this->attributes[$name];
	}
	
	/**
	 * @return string
	 */
	public final function getLocaleMessage()
	{
		$key = $this->key; 
		if (!empty($key))
		{
			return LocaleService::getInstance()->transBO($key, array('ucf'), $this->attributes);
		}
		return $this->getMessage();
	}

	/**
	 * @param string[html | xml] $format
	 */
	public function printStackTrace ($format = 'html')
	{
		switch ($format)
		{
			case 'html':
				$renderer = new exception_HtmlRenderer();
				$renderer->printStackTrace($this);
				break;
			case 'xml':
				$renderer = new exception_XmlRenderer();
				$renderer->printStackTrace($this);	
				break;			
			default:
				echo $this->__toString();
		}
	}
	
	// Deprecated
		
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	public function shutdown() { }
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	function getClassName()
	{
		$trace = $this->getTrace();
		if (isset($trace[0]["class"]))
		{
			return $trace[0]["class"];
		}
		return 'N/A';
	}
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	function isErrorCode($errorCode)
	{
		return $errorCode == $this->getMessage();
	}
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	function getClassError()
	{
		return $this->getMessage();
	}

	/**
	 * @deprecated (will be removed in 4.0)
	 */
	function getId()
	{
		return $this->getMessage();
	}
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	public function setLocaleFilePath($localeFilePath)
	{
		
	}
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */	
	public function setLocaleFilePathFromClassName($className)
	{
		
	}
}
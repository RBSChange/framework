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

	public final function getKey()
	{
		return $this->key;
	}
	
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
			return f_Locale::translateUI('&' . $key . ';', $this->attributes);
		}
		return $this->getMessage();
	}
		
	/**
	 * @deprecated 
	 */
	public function shutdown() { }
	
	/**
	 * @deprecated
	 * @return string
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
	 * @deprecated 
	 * @param string $errorCode
	 * @return boolean
	 */
	function isErrorCode($errorCode)
	{
		return $errorCode == $this->getMessage();
	}
	
	/**
	 * @deprecated 
	 * @return string
	 */
	function getClassError()
	{
		return $this->getMessage();
	}

	/**
	 * @deprecated 
	 * @return string
	 */
	function getId()
	{
		return $this->getMessage();
	}
	
	/**
	 * @deprecated 
	 * @param string $localeFilePath
	 */
	public function setLocaleFilePath($localeFilePath)
	{
		
	}
	
	/**
	 * @deprecated 
	 * @param string $className
	 */	
	public function setLocaleFilePathFromClassName($className)
	{
		
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
}
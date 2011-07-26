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
			return LocaleService::getInstance()->transBO($key, array(), $this->attributes);
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
}
<?php
/**
 * @author intportg
 * @package framework.persistentdocument.filter
 */
abstract class f_persistentdocument_DocumentFilterParameter
{
	/**
	 * @return Mixed
	 */
	abstract public function getValueForQuery();

	/**
	 * @return String
	 */
	abstract public function getValueAsText();

	/**
	 * @return String
	 */
	abstract public function getValueForXul();

	/**
	 * @return String
	 */
	abstract public function getValueForJson();
	
	
	/**
	 * @var array
	 */
	private $customAttributes;
	
	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getCustomAttribute($name)
	{
		if (is_array($this->customAttributes) && isset($this->customAttributes[$name]))
		{
			return $this->customAttributes[$name];
		}
		return null;
	}
	
	/**
	 * @param string $propertyName
	 * @param string $name
	 * @return mixed
	 */
	public function getCustomPropertyAttribute($propertyName, $name)
	{
		if (is_array($this->customAttributes) && 
			isset($this->customAttributes[$propertyName]) && 
			isset($this->customAttributes[$propertyName][$name]))
		{
			return $this->customAttributes[$propertyName][$name];
		}
		return null;
	}
	
	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setCustomAttribute($name, $value)
	{
		if (!is_array($this->customAttributes))
		{
			$this->customAttributes = array($name => $value);
		}
		else
		{
			$this->customAttributes[$name] = $value;
		}
	}

	/**
	 * @param string $propertyName
	 * @param string $name
	 * @param mixed $value
	 */
	public function setCustomPropertyAttribute($propertyName, $name, $value)
	{
		if (!is_array($this->customAttributes))
		{
			$this->customAttributes = array($propertyName => array($name => $value));
		}
		else if (!isset($this->customAttributes[$propertyName]))
		{
			$this->customAttributes[$propertyName] = array($name => $value);
		}
		else
		{
			$this->customAttributes[$propertyName][$name] = $value;
		}
	}	
}
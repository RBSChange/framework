<?php
class indexer_FacetCount
{
	private $value;
	private $count;

	function __construct($value, $count)
	{
		$this->value = $value;
		$this->count = $count;
	}

	/**
	 * @return String
	 */
	function getValue()
	{
		return $this->value;
	}
	
	/**
	 * @param String $value
	 */
	function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * @return String
	 */
	function getValueUcFirst()
	{
		return f_util_StringUtils::ucfirst($this->value);
	}

	/**
	 * @return unknown
	 */
	function getCount()
	{
		return $this->count;
	}

	function isEmpty()
	{
		return $this->count == 0;
	}
}
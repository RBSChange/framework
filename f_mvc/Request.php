<?php
interface f_mvc_Request
{
	/**
	 * @param String $name
	 * @param String $defaultValue
	 * @return String the value of the parameter or $defaultValue
	 */
	function getParameter($name, $defaultValue = null);
	
	/**
	 * @return array<String, array<String>>
	 */
	function getParameters();
	
	/**
	 * @param String $name
	 * @return Boolean
	 */
	function hasParameter($name);
	
	/**
	 * @param String $name
	 * @return Boolean
	 */
	function hasNonEmptyParameter($name);
	
	/**
	 * @param String $name
	 * @param mixed $value
	 */
	function setAttribute($name, $value);
	
	/**
	 * @param String $name
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	function getAttribute($name, $defaultValue = null);
	
	/**
	 * @return array<String, mixed>
	 */
	function getAttributes();
	
	/**
	 * @param String $name
	 * @return Boolean
	 */
	function hasAttribute($name);
}
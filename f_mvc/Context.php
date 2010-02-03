<?php
interface f_mvc_Context
{

	/**
	 * @param String $name
	 * @param mixed $value
	 */
	function setAttribute($name, $value);
	
	/**
	 * @param String $name
	 * @param String $defaultValue
	 * @return mixed
	 */
	function getAttribute($name, $defaultValue = null);
	
	/**
	 * @return array<String, mixed>
	 */
	function getAttributes();
	
	/**
	 * @param String $name
	 */
	function removeAttribute($name);
	
	/**
	 * @param String $name
	 * @return Boolean
	 */
	function hasAttribute($name);
	
	/**
	 * @param String $name
	 * @return Boolean
	 */
	function hasNonEmptyAttribute($name);
}
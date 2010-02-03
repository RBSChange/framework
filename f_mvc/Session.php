<?php
interface f_mvc_Session
{
	/**
	 * @param String $name
	 * @param mixed $value
	 */
	function setAttribute($name, $value);
	
	/**
	 * @param String $name
	 * @return mixed
	 */
	function getAttribute($name);
	
	/**
	 * @return array<String, mixed>
	 */
	function getAttributes();
	
	/**
	 * @param String $name
	 */
	function removeAttribute($name);
}
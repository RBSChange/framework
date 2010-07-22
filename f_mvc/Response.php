<?php
interface f_mvc_Response
{
	/**
	 * Set the HTTP header $name to value $value
	 * 
	 * @param String $name
	 * @param String $value
	 */
	function setHeader($name, $value);
	
	/**
	 * Set the HTTP status to value
	 * 
	 * @param String $value
	 */
	function setStatus($value);
	
	/**
	 * @param String $value
	 */
	function setContentType($value);
	
	/**
	 * @return String
	 */
	function getContentType();
	
	/**
	 * tells wether or not the response has been committed
	 * 
	 * @return Boolean
	 */
	function isCommitted();
	
	/**
	 * Reset the response
	 */
	function reset();
	
	/**
	 * @return f_mvc_Writer
	 */
	function getWriter();
	
	/**
	 * @param String
	 */
	function write($string);
}
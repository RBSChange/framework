<?php
/**
 * @package framework.loader
 */
interface ResourceLoader
{

	/**
	 * Return void or an object
	 *
	 * @param string $name name of researched resource
	 * @return mixed void or an object
	 */
	public function load($name);
	
}


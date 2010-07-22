<?php
/**
 * @package framework.loader
 */
interface ResourceResolver
{

	/**
	 * Return the path of the researched resource
	 *
	 * @param string $name name of researched resource
	 * @return string Path of resource
	 */
	public function getPath($name);
	
}


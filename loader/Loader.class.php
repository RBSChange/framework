<?php
/**
 * @package framework.loader
 */
abstract class Loader
{
		
	/**
	 * Return the current ClassLoader
	 *
	 * @return ResourceLoader Instance of defined Loader
	 */
	public static function getInstance( $type )
	{
		$className = ucfirst( strtolower( $type ) ) . "Loader";
		$method = new ReflectionMethod($className, 'getInstance');
		return $method->invoke(null);
	
	}
	
}


<?php
/**
 * @package framework.loader
 */
abstract class Resolver
{
		
	/**
	 * Return the current ClassResolver
	 *
	 * @return ResourceResolver of defined Resolver
	 */
	public static function getInstance( $type )
	{
		$className = ucfirst( strtolower( $type ) ) . "Resolver";
		$method = new ReflectionMethod($className, 'getInstance');
		return $method->invoke(null);
	
	}
	
}


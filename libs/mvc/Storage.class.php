<?php
/**
 * @deprecated use \Change\Mvc\Storage
 */
class change_Storage extends \Change\Mvc\Storage
{
	/**
	 * @param string $class
	 * @return change_Storage 
	 */
	public static function newInstance($class)
	{
		$object = new $class();
		if (!($object instanceof change_Storage))
		{
			$error = 'Class "' .$class .'" is not of the type change_Storage';
			throw new Exception($error);
		}
		return $object;
	}
}
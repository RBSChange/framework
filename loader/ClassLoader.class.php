<?php
/**
 * @package framework.loader
 */
class ClassLoader implements ResourceLoader
{

	/**
	 * The singleton instance
	 * @var ClassLoader
	 */
	private static $instance = null;

	/**
	 * Class resolver to find the path of class
	 * @var ClassResolver
	 */
	private $resolver = null;

	/**
	 * Construct of ClassLoader where the resolver class instance is setted
	 */
	private function __construct()
	{
		$this->resolver = ClassResolver::getInstance();
	}

	/**
	 * Return the current ClassLoader
	 *
	 * @return ClassLoader
	 */
	public static function getInstance()
	{
		if( is_null(self::$instance) )
		{
			self::$instance = new ClassLoader();
		}

		return self::$instance;
	}

	/**
	 * Get the path of $className and make a require_once
	 *
	 * @param string $className Name of researched class
	 * @return true
	 */
	public function load($className)
	{
		require_once($this->resolver->getPath($className));
		return true;
	}

	/**
	 * @param string $className
	 * @return boolean
	 */
	function autoload($className)
	{
		$classFilePath = $this->resolver->getPathOrNull($className);
		if ($classFilePath && is_readable($classFilePath))
		{
			require_once($classFilePath);
			return true;
		}
		return false;
	}

	/**
	 * @param string $className Name of researched class
	 * @return boolean true if exists
	 */
	public function exists($className)
	{
		$path = $this->resolver->getPathOrNull($className);
		if ($path !== null && realpath($path) !== false)
		{
			require_once($path);
			return true;
		}
		return false;
	}

	/**
	 * @param string $className Name of researched class
	 * @return boolean true if exists
	 */
	public function existsNoLoad($className)
	{
		$path = $this->resolver->getPathOrNull($className);
		if ($path !== null && realpath($path) !== false)
		{
			return true;
		}
		return false;
	}
}

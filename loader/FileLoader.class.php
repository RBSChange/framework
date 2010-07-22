<?php
/**
 * @package framework.loader
 */
class FileLoader implements ResourceLoader
{

	/**
	 * The singleton instance
	 * @var FileLoader
	 */
	private static $instance = null;

	/**
	 * @var FileResolver
	 */
	protected $resolver;

	protected function __construct()
	{
		$this->resolver = FileResolver::getInstance();
	}

	/**
	 * Return the current FileLoader
	 *
	 * @return FileLoader
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new FileLoader();
		}

		return self::$instance;
	}

	/**
	 * Get the path of $filename and make a require_once
	 *
	 * @param string $filename Name of researched file
	 * @return string
	 */
	public function load($filename)
	{
		return  $this->resolver->getPath($filename);
	}

	/**
	 * Set the package name to determine the relative path to search
	 *
	 * @param string $packageName Package name (framework, modules_generic, libs_agavi, ... )
	 * @return FileLoader
	 */
	public function setPackageName($packageName)
	{

		$this->resolver->setPackageName($packageName);
		return $this;

	}

	/**
	 * @return String
	 */
	public function getPackageName()
	{
		return $this->resolver->getPackageName();
	}

	/**
	 * @param string $directory
	 * @return FileLoader
	 */
	public function setDirectory($directory)
	{
		$this->resolver->setDirectory($directory);
		return $this;
	}

	/**
	 * @return String
	 */
	public function getDirectory()
	{
		return $this->resolver->getDirectory();
	}

	/**
	 * Reset the loader to use its default values (no directory and no packageName set).
	 *
	 * @return FileLoader
	 */
	public function reset()
	{
		$this->resolver->reset();
		return $this;
	}

}

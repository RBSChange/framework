<?php
$frameworkPath = dirname(dirname(dirname(__FILE__)));
require_once($frameworkPath . '/loader/ResourceResolver.class.php');
require_once($frameworkPath . '/loader/ClassResolver.class.php');
require_once($frameworkPath . '/util/FileUtils.class.php');
require_once($frameworkPath . '/util/StringUtils.class.php');

class cboot_ClassDirAnalyzer extends ClassResolver
{
	
	/**
	 * @var cboot_ClassDirAnalyzer
	 */
	private static $instance = null;

	/**
	 * @return cboot_ClassDirAnalyzer
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
		
	/**
	 * @param string $className
	 * @return boolean
	 */
	function autoload($className)
	{
		try
		{
			require_once($this->getPath($className));
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
}
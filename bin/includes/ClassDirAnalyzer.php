<?php
$frameworkPath = dirname(dirname(dirname(__FILE__)));
require_once($frameworkPath . '/loader/ResourceResolver.class.php');
require_once($frameworkPath . '/loader/ClassResolver.class.php');
require_once($frameworkPath . '/util/FileUtils.class.php');
require_once($frameworkPath . '/util/StringUtils.class.php');
require_once($frameworkPath . '/util/Finder.class.php');

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
	
	protected function __construct()
	{
		$this->cacheDir = WEBEDIT_HOME . '/cache/autoload';
		if (!is_dir($this->cacheDir))
		{
			$this->initialize();
		}
	}
	
	/**
	 * Launch this to create the cache of class
	 */
	public function initialize()
	{
		$this->appendDir(WEBEDIT_HOME . '/framework');
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
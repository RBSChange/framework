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
		$path = $this->resolver->getPath($className);
		require_once($path);
		return true;
	}

	/**
	 * @param string $className
	 * @return boolean
	 */
	function autoload($className)
	{
		try
		{
			return $this->load($className);
		}
		catch (Exception $e)
		{
			// unspecified class
			// do not print an error if the autoload came from class_exists
			$trace = debug_backtrace();
			if (count($trace) < 1 || ($trace[1]['function'] != 'class_exists' && $trace[1]['function'] != 'is_a'))
			{
				if (php_sapi_name() == "cli")
				{
					echo "Unable to autoload $className.\n";
					echo "You should run 'change.php update-autoload --refresh-cli-autoload'.\n";
					echo "If it persists, see phperror.log to trace the call.\n";
				}
				ob_start();
				debug_print_backtrace();
				$trace = ob_get_contents();
				ob_end_clean();
				$rc = fopen(CHANGE_LOG_DIR . DIRECTORY_SEPARATOR .'phperror.log', 'a+');
				fwrite($rc, $trace);
				fclose($rc);
			}
			return false;
		}
	}

	/**
	 * @param string $className Name of researched class
	 * @return boolean true if exists
	 */
	public function exists($className)
	{
		$path = $this->resolver->getPathOrNull($className);
		if ($path !== null && file_exists(realpath($path)))
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
		if ($path !== null && file_exists(realpath($path)))
		{
			return true;
		}
		return false;
	}
}

<?php
class ClassResolver implements ResourceResolver
{
	/**
	 * the singleton instance
	 * @var ClassResolver
	 */
	private static $instance = null;
	protected $cacheDir = null;
	

	private $keys;
	private $reps;
	
	protected function __construct()
	{
		require_once (PROJECT_HOME . '/framework/util/FileUtils.class.php');
		require_once (PROJECT_HOME . '/framework/util/StringUtils.class.php');
		
		$this->keys = array('%PROJECT_HOME%');
		$this->reps = array(PROJECT_HOME);
		
		$this->cacheDir = PROJECT_HOME . '/cache/autoload';
		
		if (! is_dir($this->cacheDir))
		{
			$this->initialize();
		}
	}
	
	/**
	 * Return the current ClassResolver
	 *
	 * @return ClassResolver
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{	
			self::$instance = new ClassResolver();
		}
		return self::$instance;
	}
	
	/**
	 * Return the path of the researched resource
	 *
	 * @param string $className Name of researched class
	 * @return string Path of resource
	 */
	public function getPath($className)
	{
		$this->validateClassName($className);
		// Support for Zend Framework
		if (strpos($className, 'Zend_') === 0)
		{
		    return ZEND_FRAMEWORK_PATH . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
		}
		return $this->cacheDir . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className) . DIRECTORY_SEPARATOR . "to_include";
	}
	

	/**
	 * @param string $className Name of researched class
	 * @return string Path of resource or null
	 */
	public function getPathOrNull($className)
	{
		return $this->getRessourcePath($className);
	}
	
	protected function validateClassName($className)
	{
		if (! preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $className))
		{
			if (Framework::inDevelopmentMode())
			{
				die("Invalid class name ".var_export($className, true));
			}
			die("Invalid class name");
		}
	}
	
	protected function getCachePath($className, $baseDir)
	{
		$this->validateClassName($className);
		return $baseDir . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className) . DIRECTORY_SEPARATOR . "to_include";
	}
	
	protected function getPathsToAnalyse()
	{
		 
		$result = array(
				array('path' => '%PROJECT_HOME%/framework', 'recursive' => 'true', 
						'exclude' => array('deprecated', 'doc', 'module', 'home', 'patch')), 
				array('path' => '%PROJECT_HOME%/libs', 'recursive' => 'true',
					'exclude' => array('fckeditor', 'icons', 'pearlibs', 'zfminimal')),
				array('path' => '%PROJECT_HOME%/build/project', 'recursive' => 'true'), 
				array('path' => '%PROJECT_HOME%/modules/*/actions'), 
				array('path' => '%PROJECT_HOME%/modules/*/change-commands', 'recursive' => 'true'), 
				array('path' => '%PROJECT_HOME%/modules/*/changedev-commands', 'recursive' => 'true'), 
				array('path' => '%PROJECT_HOME%/modules/*/lib', 'recursive' => 'true'), 
				array('path' => '%PROJECT_HOME%/modules/*/views'), 
				array('path' => '%PROJECT_HOME%/modules/*/persistentdocument', 'recursive' => 'true'), 
				array('path' => '%PROJECT_HOME%/override/modules/*/actions'), 
				array('path' => '%PROJECT_HOME%/override/modules/*/lib', 'recursive' => 'true'), 
				array('path' => '%PROJECT_HOME%/override/modules/*/views'));
				
		if (defined('PEAR_DIR'))
		{
			array_unshift($result, array('path' => PEAR_DIR, 'recursive' => 'true'));
		}
		return $result;
	}
	
	/**
	 * Launch this to create the cache of class
	 */
	public function initialize()
	{
		$ini = $this->getPathsToAnalyse();		
		// let's do our fancy work
		foreach ($ini as $entry)
		{
			// directory mapping
			$path = $this->replaceConstants($entry['path']);			
			
			$exclude = array();
			if (isset($entry['exclude']) && is_array($entry['exclude']))
			{
				$exclude = $entry['exclude'];
			}
			
			$recursive = ((isset($entry['recursive'])) ? $entry['recursive'] : false);			
			if ($recursive)
			{
				$exclude = array_merge($exclude, array('.git', '.svn'));
			}
			foreach ($this->glob($path) as $phpPath) 
			{
				$files = $this->findFile($phpPath, $recursive, $exclude);
				if (Framework::isInfoEnabled())
				{
					Framework::info('Scanning'. ($recursive ? ' recursive: ' : ': ') . $phpPath . ' -> ' . count($files) . ' files');
				}
				$this->constructClassList($files);
			}		
		}
	}
	
	/**
	 * @param string $path
	 * @return string[]
	 */
	private function glob($path)
	{
		if (strpos($path, '*') !== false)
		{
			$result = glob($path);
			return (is_array($result)) ? $result : array();
		}
		elseif (is_dir($path))
		{
			return array($path);
		}
		return array();
	}
	
	/**
	 * @param string $phpPath
	 * @param boolean $recursive
	 * @param string[] $exclude
	 */
	private function findFile($phpPath, $recursive, $exclude)
	{
		$files = array();
		if (is_dir($phpPath))
		{
			$dh = opendir($phpPath);
			$bp = $phpPath. DIRECTORY_SEPARATOR;
			
	        while ($fileName = readdir($dh))
	        {
	            if ($fileName === '.' || $fileName === '..') {continue;}

                if (is_file($bp . $fileName))
                {
                    if (substr($fileName, -4) === '.php')
                    {
                    	$files[] = realpath($bp .$fileName);
                    }
                }
                elseif (is_dir($bp .$fileName) && $recursive && !in_array($fileName, $exclude))
                {
                  	$files = array_merge($files, $this->findFile($bp .$fileName, $recursive, $exclude));
                }
	        }
	        closedir($dh);
		}
		return $files;
	}
	
	/**
	 * Update the autoload cache
	 */
	public function update()
	{
		$oldCacheDir = $this->cacheDir;
		$this->cacheDir = PROJECT_HOME . '/cache/autoload_tmp';
		try
		{
			f_util_FileUtils::rmdir($this->cacheDir);
			$this->initialize();
			rename($oldCacheDir, $oldCacheDir . '.old');
			rename($this->cacheDir, $oldCacheDir);
			f_util_FileUtils::rmdir($oldCacheDir . '.old');
		
		}
		catch (Exception $e)
		{
			die($e->getMessage());
		}
		$this->cacheDir = $oldCacheDir;
	}

	
	/**
	 * @param String $resource
	 * @return The path of the ressource or null if the intended path does not exists
	 */
	function getRessourcePath($resource)
	{
		$cacheFile = $this->getCachePath($resource, $this->cacheDir);
		if (file_exists($cacheFile))
		{
			return $cacheFile;
		}
		return null;
	}
	
	/**
	 * @param String $class full class name
	 * @param String $filePath the file defining the class
	 * @param Boolean $override
	 * @return String the cache path
	 */
	public function appendToAutoloadFile($class, $filePath, $override = false)
	{
		$cacheFile = $this->getCachePath($class, $this->cacheDir);
		if (!file_exists($cacheFile))
		{
			if (is_link($cacheFile)) {@unlink($cacheFile);}
			f_util_FileUtils::mkdir(dirname($cacheFile));
			symlink($filePath, $cacheFile);
		}
		else if ($override)
		{
			unlink($cacheFile);
			symlink($filePath, $cacheFile);
		}
		return $cacheFile;
	}
		
	/**
	 * @param String $filePath
	 * @param Boolean $override
	 * @return String[] the name of the classes defined in the file
	 */
	public function appendFile($filePath, $override = false)
	{
		return $this->constructClassList(array($filePath), $override);
	}
	
	public function appendRealDir($dirPath)
	{
		$this->appendDir($dirPath, true);	
	}
	
	/**
	 * @param String $dirPath
	 * @param Boolean $override
	 */
	public function appendDir($dirPath, $override = false)
	{
		$ini = $this->getPathsToAnalyse();
		
		// let's do our fancy work
		foreach ($ini as $entry)
		{
			// directory mapping
			$path = $this->replaceConstants($entry['path']);			
			$exclude = array();
			if (isset($entry['exclude']) && is_array($entry['exclude']))
			{
				$exclude = $entry['exclude'];
			}
			
			$recursive = ((isset($entry['recursive'])) ? $entry['recursive'] : false);			
			if ($recursive)
			{
				$exclude = array_merge($exclude, array('.git', '.svn'));
			}
			
			$realDirPath = realpath($dirPath);
			
			foreach ($this->glob($path) as $searchPath) 
			{
				$realSearchPath = realpath($searchPath);
								
				if (strpos($realSearchPath, $realDirPath) === 0)
				{	
					$files = $this->findFile($searchPath, $recursive, $exclude);
					$this->constructClassList($files);
				}
				else if (strpos($realDirPath, $realSearchPath) === 0 && $recursive)
				{
					if (!in_array(basename($dirPath), $exclude))
					{	
						$files = $this->findFile($dirPath, $recursive, $exclude);
						$this->constructClassList($files);	
					}
					return;			
				}
			}

		}
	}
	
	/**
	 * @param array $files
	 * @param Boolean $override
	 */
	private function constructClassList($files, $override = false)
	{
		foreach ($files as $file)
		{
			$tokenArray = token_get_all(file_get_contents($file));
			$definedClasses = array();
			$cachePaths = array();
			foreach ($tokenArray as $index => $token)
			{
				if ($token[0] == T_CLASS || $token[0] == T_INTERFACE)
				{
					$className = $tokenArray[$index + 2][1];
					$definedClasses[] = $className;
					$cachePaths[$className] = $this->appendToAutoloadFile($className, $file, $override);
				}
			}
		}
	}

	private function replaceConstants($value)
	{
		return str_replace($this->keys, $this->reps, $value);
	}
}
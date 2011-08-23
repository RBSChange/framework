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
		return $this->cacheDir . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, 
				$className) . DIRECTORY_SEPARATOR . "to_include";
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
				array('path' => '%PROJECT_HOME%/framework/', 'recursive' => 'true', 
						'exclude' => array('deprecated', 'doc', 'module', 'webapp', 'patch')), 
				array('path' => '%PROJECT_HOME%/libs/', 'recursive' => 'true',
					'exclude' => array('fckeditor', 'icons', 'pearlibs')),
				array('path' => '%PROJECT_HOME%/build/project/', 'recursive' => 'true'), 
				array('path' => '%PROJECT_HOME%/modules/*/actions'), 
				array('path' => '%PROJECT_HOME%/modules/*/change-commands', 'recursive' => 'true'), 
				array('path' => '%PROJECT_HOME%/modules/*/changedev-commands', 'recursive' => 'true'), 
				array('path' => '%PROJECT_HOME%/modules/*/lib/', 'recursive' => 'true'), 
				array('path' => '%PROJECT_HOME%/modules/*/views/'), 
				array('path' => '%PROJECT_HOME%/modules/*/persistentdocument/', 'recursive' => 'true'), 
				array('path' => '%PROJECT_HOME%/override/modules/*/actions'), 
				array('path' => '%PROJECT_HOME%/override/modules/*/lib/', 'recursive' => 'true'), 
				array('path' => '%PROJECT_HOME%/override/modules/*/views/'));
				
		if (defined('PEAR_DIR'))
		{
			array_unshift($result, array('path' => PEAR_DIR . '/', 'recursive' => 'true'));
		}
		return $result;
	}
	
	/**
	 * Launch this to create the cache of class
	 */
	public function initialize()
	{
		$ini = $this->getPathsToAnalyse();
		// we automatically add our php classes
		require_once (PROJECT_HOME . '/framework/util/Finder.class.php');
		$ext = '.php';
		
		// let's do our fancy work
		foreach ($ini as $entry)
		{
			// directory mapping
			$path = $this->replaceConstants($entry['path']);
			$finder = f_util_Finder::type('file')->ignore_version_control()->name('*' . $ext);
			$finder->follow_link();
			
			// recursive mapping?
			$recursive = ((isset($entry['recursive'])) ? $entry['recursive'] : false);
			
			if (!$recursive)
			{
				$finder->maxdepth(0);
			}
			
			// exclude files or directories?
			if (isset($entry['exclude']))
			{
				if (! is_array($entry['exclude']))
				{
					$entry['exclude'] = explode(',', $entry['exclude']);
				}
				$finder->prune($entry['exclude'])->discard($entry['exclude']);
			}
			
			$matches = $this->glob($path);
			if ($matches)
			{
				$files = $finder->in($matches);
				$this->constructClassList($files);
			}			
		}
	}
	
	private function glob($path)
	{
		if (strpos($path, '*') !== false && basename($path) !== '*')
		{
			$result = glob($path);
			return (is_array($result) && count($result) > 0) ? $result : false;
		}
		$cleanPath = str_replace('/*', '', $path);
		$result = array($cleanPath);
		if (is_dir($cleanPath))
		{
			foreach (new DirectoryIterator($cleanPath) as $fileInfo)
			{
				if (! $fileInfo->isDot())
				{
					$result[] = realpath($fileInfo->getPathname());
				}
			}
		}
		return count($result) > 0 ? $result : false;
	}
	
	/**
	 * @param String $basePattern
	 * @return String[]
	 * @example getClassNames() return all available classes name
	 * @example getCLassNames("web") return all available classes that the name starts with "web"
	 */
	function getClassNames($basePattern = null)
	{
		$classNames = array();
		$path = PROJECT_HOME . '/cache/autoload';
		$pathLength = strlen($path) + 1;
		
		if ($basePattern !== null)
		{
			$patternDirs = explode("_", $basePattern);
			
			$lastPatternPart = $patternDirs[count($patternDirs) - 1];
			unset($patternDirs[count($patternDirs) - 1]);
			if (count($patternDirs) > 0)
			{
				$path .= "/" . join("/", $patternDirs);
			}
		}
		else
		{
			$lastPatternPart = '';
		}
		if ($lastPatternPart != '')
		{
			foreach (glob($path . "/" . $lastPatternPart . "*", 
					GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT) as $subPath)
			{
				foreach (f_util_FileUtils::find("to_include", $subPath) as $toIncludePath)
				{
					$classNames[] = substr(str_replace("/", "_", dirname($toIncludePath)), 
							$pathLength);
				}
			}
		}
		else
		{
			foreach (f_util_FileUtils::find("to_include", $path) as $toIncludePath)
			{
				$classNames[] = substr(str_replace("/", "_", dirname($toIncludePath)), $pathLength);
			}
		}
		return $classNames;
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
		if (! file_exists($cacheFile))
		{
			if (is_link($cacheFile))
			{
				@unlink($cacheFile);
			}
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
	 * Returns an array containing all the defined and autoloaded classes.
	 *
	 * @return array<string>
	 */
	public final function getDefinedClasses()
	{
		throw new Exception("ClassResolver->getDefinedClasses() is not implemented ; please do it");
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
		foreach (f_util_FileUtils::find("*.php", $dirPath) as $filePath)
		{
			$this->appendFile($filePath, true);
		}		
	}
	
	/**
	 * @param String $dirPath
	 * @param Boolean $override
	 */
	public function appendDir($dirPath, $override = false)
	{
		$ini = $this->getPathsToAnalyse();
		// we automatically add our php classes
		require_once (PROJECT_HOME . '/framework/util/Finder.class.php');
		$ext = '.php';		
		// let's do our fancy work
		foreach ($ini as $entry)
		{
			// directory mapping
			$path = $this->replaceConstants($entry['path']);
			$finder = f_util_Finder::type('file')->ignore_version_control()->name('*' . $ext);
			$finder->follow_link();
			
			// recursive mapping?
			$recursive = ((isset($entry['recursive'])) ? $entry['recursive'] : false);
			
			if (!$recursive)
			{
				$finder->maxdepth(0);
			}
			
			// exclude files or directories?
			if (isset($entry['exclude']))
			{
				if (! is_array($entry['exclude']))
				{
					$entry['exclude'] = explode(',', $entry['exclude']);
				}
				$finder->prune($entry['exclude'])->discard($entry['exclude']);
			}
			
			$matches = $this->glob($path);
			if ($matches)
			{
				foreach ($matches as $path) 
				{
					if (strpos($path, $dirPath) === 0)
					{
						$files = $finder->in(array($path));
						$this->constructClassList($files);
					}
					else if (strpos($dirPath, $path) === 0 && $recursive)
					{
						$files = $finder->in(array($dirPath));
						$this->constructClassList($files);	
						return;			
					}
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
            
			if (count($definedClasses) > 1)
			{
				$definedSer = serialize($definedClasses);
				foreach ($cachePaths as $className => $cachePath)
				{
					f_util_FileUtils::write($cachePath . ".classes", $definedSer);
				}
			}
		}
	}

	private function replaceConstants($value)
	{
		return str_replace($this->keys, $this->reps, $value);
	}
}
<?php
class change_AutoloadBuilder
{
	/**
	 * @var change_AutoloadBuilder
	 */
	private static $instance = null;
	
	/**
	 * @return change_AutoloadBuilder
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private $basePath = null;

	private $keys;
	private $reps;
	
	protected function __construct()
	{	
		$this->keys = array('%PROJECT_HOME%');
		$this->reps = array(PROJECT_HOME);
		$this->basePath = PROJECT_HOME . '/build/autoload';
	}
	
	/**
	 * Update the autoload cache
	 */
	public function update()
	{
		clearstatcache();
		$oldCacheDir = $this->basePath;		
		$this->basePath = PROJECT_HOME . '/cache/autoload_tmp';
		try
		{
			$this->rmdir($this->basePath);
			$this->initialize();
			$oldPath = PROJECT_HOME . '/cache/autoload_old';		
			rename($oldCacheDir, $oldPath);
			rename($this->basePath, $oldCacheDir);
			$this->rmdir($oldPath);
		}
		catch (Exception $e)
		{
			die($e->getMessage());
		}
		$this->basePath = $oldCacheDir;
	}
	
	/**
	 * @param string $dirPath
	 */
	public function appendDir($dirPath)
	{
		clearstatcache();
		if (is_dir($dirPath))
		{
			foreach ($this->getPathsToAnalyse() as $entry)
			{
				$path = $this->replaceConstants($entry['path']);
				$exclude = (isset($entry['exclude']) && is_array($entry['exclude'])) ? $entry['exclude'] : array();
				$realDirPath = realpath($dirPath);
					
				foreach ($this->glob($path) as $searchPath)
				{
					$realSearchPath = realpath($searchPath);
					if (strpos($realSearchPath, $realDirPath) === 0)
					{
						$files = $this->getPHPFiles($searchPath, $exclude);
						$this->constructClassList($files, true);
					}
					else if (strpos($realDirPath, $realSearchPath) === 0)
					{
						if (!in_array(basename($dirPath), $exclude))
						{
							$files = $this->getPHPFiles($realDirPath, $exclude);
							$this->constructClassList($files, true);
						}
						return;
					}
				}
			}
		}
	}
	
	/**
	 * @param string $phpFilePath
	 */
	public function appendFile($phpFilePath)
	{
		clearstatcache();
		if (is_readable($phpFilePath) && substr($phpFilePath, -4) == '.php')
		{
			$this->constructClassList(array($phpFilePath), true);
		}
	}
	
	/**
	 * @param string $className
	 * @return string|false
	 */
	public function buildLinkPathByClass($className)
	{
		if (is_string($className) && !empty($className) && preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $className))
		{
			$path =  $this->basePath . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className) . DIRECTORY_SEPARATOR . "to_include";
			return $path;
		}
		return false;
	}
	
	private function rmdir($directoryPath)
	{
		if (is_dir($directoryPath))
		{
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS), 
				RecursiveIteratorIterator::CHILD_FIRST) as $file => $info)
			{
				if (is_dir($file)) 
				{
					rmdir($file);
				}
				else
				{
					unlink($file);
				}
			}
			rmdir($directoryPath);
		}
	}
	
	private function initialize()
	{	
		if (!is_dir($this->basePath))
		{
			mkdir($this->basePath, 0777, true);
		}
		
		foreach ($this->getPathsToAnalyse() as $entry)
		{
			$path = $this->replaceConstants($entry['path']);			
			$exclude = (isset($entry['exclude']) && is_array($entry['exclude'])) ? $entry['exclude'] : array();
	
			foreach ($this->glob($path) as $phpPath)
			{
				$files = $this->getPHPFiles($phpPath, $exclude);
				$this->constructClassList($files);
			}
		}
	}
	
	private function getPathsToAnalyse()
	{	 
		return array(
				array('path' => '%PROJECT_HOME%/framework', 'exclude' => array('home', 'patch')), 
				array('path' => '%PROJECT_HOME%/build/project'), 
				array('path' => '%PROJECT_HOME%/modules/*/actions'), 
				array('path' => '%PROJECT_HOME%/modules/*/commands'), 
				array('path' => '%PROJECT_HOME%/modules/*/lib'), 
				array('path' => '%PROJECT_HOME%/modules/*/views'), 
				array('path' => '%PROJECT_HOME%/modules/*/persistentdocument'), 
				array('path' => '%PROJECT_HOME%/override/modules/*/actions'), 
				array('path' => '%PROJECT_HOME%/override/modules/*/lib'), 
				array('path' => '%PROJECT_HOME%/override/modules/*/views'));
	}
	
	private function replaceConstants($value)
	{
		return str_replace($this->keys, $this->reps, $value);
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
	 * @param string $path
	 * @param string $exludeDirs
	 * @return string[]
	 */
	private function getPHPFiles($path, $exludeDirs)
	{
		$result = array();
		$di = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::KEY_AS_PATHNAME);
	
		f_PHPFileFilter::setFilters($exludeDirs);
		$fi = new f_PHPFileFilter($di);
		$it = new RecursiveIteratorIterator($fi, RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($it as $file => $info)
		{
			if ($info->isFile())
			{
				$result[] = $file;
			}
		}
		return $result;
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
			foreach ($tokenArray as $index => $token)
			{
				if ($token[0] == T_CLASS || $token[0] == T_INTERFACE)
				{
					$className = $tokenArray[$index + 2][1];
					$this->makeAutoloadLink($className, $file, $override);
				}
			}
		}
	}
		
	/**
	 * @param String $class full class name
	 * @param String $filePath the file defining the class
	 * @param Boolean $override
	 */
	private function makeAutoloadLink($class, $filePath, $override)
	{
		$cacheFile = $this->makeClassLinkPath($class, $this->basePath);
		if (!file_exists($cacheFile))
		{
			if (is_link($cacheFile)) {unlink($cacheFile);}
			$d = dirname($cacheFile);
			if (!is_dir($d)) {mkdir($d, 0777, true);}
			symlink($filePath, $cacheFile);
		}
		else if ($override)
		{
			unlink($cacheFile);
			symlink($filePath, $cacheFile);
		}
	}
	
	private function makeClassLinkPath($className, $baseDir)
	{
		return $baseDir . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className) . DIRECTORY_SEPARATOR . "to_include";
	}
}

class f_PHPFileFilter extends RecursiveFilterIterator
{
	public static $excludeDirs;

	public static function setFilters($exludeDirs)
	{
		self::$excludeDirs = array_merge(array('.svn', '.git'), $exludeDirs);
	}

	public function accept()
	{
		$c = $this->current();
		if ($c->isDir())
		{
			if (in_array($c->getFilename(), self::$excludeDirs))
			{
				return false;
			}
			return true;
		}
		elseif ($c->isFile() && substr($c->getFilename(), -4) == '.php')
		{
			return true;
		}
		return false;
	}
}
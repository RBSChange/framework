<?php
/**
 * @package framework.loader
 */
class FileResolver implements ResourceResolver
{
	/**
	 * the singleton instance
	 * @var ResourceResolver
	 */
	private static $instance = null;

	/**
	 * Package name where the Resolver must search the file (framework, modules_generic, libs_agavi, ... )
	 * @var string
	 */
	private $packageName = null;

	/**
	 * @var string
	 */
	private $directory = null;

	protected function __construct()
	{

	}

	/**
	 * Return the current FileResolver
	 *
	 * @return FileResolver
	 */
	public static function getInstance()
	{

		if( is_null(self::$instance) )
		{
			self::$instance = new self();
		}
		self::$instance->reset();
		return self::$instance;

	}

	/**
	 * Reset the resolver to use its default values (no directory and no packageName set).
	 *
	 * @return FileResolver $this
	 */
	public function reset()
	{
		$this->directory = null;
		$this->packageName = null;
		$this->resetPotentialDirectories();
		return $this;
	}

	/**
	 * Return the path of the researched resource
	 *
	 * @param string $fileName Name of researched file
	 * @return string Path of resource
	 */
	public function getPath($fileName)
	{
		return $this->resolvePath($fileName);
	}

	/**
	 * Return all paths for the researched resource
	 *
	 * @param string $fileName Name of researched file
	 * @return array Paths of resource or NULL
	 */
	public function getPaths($fileName)
	{
		return $this->resolvePaths($fileName);
	}

	/**
	 * Set the package name to determine the relative path to search
	 *
	 * @param string $packageName Package name (framework, modules_generic, libs_agavi, ... )
	 * @return FileResolver $this
	 */
	public function setPackageName($packageName)
	{
		$this->packageName = str_replace('_', DIRECTORY_SEPARATOR, $packageName);
		return $this;
	}

	/**
	 * @return String
	 */
	public function getPackageName()
	{
		return $this->packageName;
	}

	/**
	 * @param string $directory
	 * @return FileResolver $this
	 */
	public function setDirectory($directory)
	{
		$this->directory = $this->cleanPath($directory);
		return $this;
	}

	/**
	 * @return String
	 */
	public function getDirectory()
	{
		return $this->directory;
	}

	/**
	 * Search the file in three directories.
	 *
	 * @param string $fileName Full name of the file
	 * @return mixed Null if file not found or String of path
	 */
	private function resolvePath($fileName)
	{
		// If package not defined throw exception because you don't know where you must search.
		if (NULL === $this->packageName)
		{
			throw new BadInitializationException('Package name must be defined. Use setPackageName($packageName).');
		}

		// Construct the relative path
		$relativePath = DIRECTORY_SEPARATOR . $this->packageName;
		if (NULL !== $this->directory)
		{
			$relativePath .= DIRECTORY_SEPARATOR . $this->directory;
		}	
		$relativePath .= DIRECTORY_SEPARATOR . $fileName;
		
		$potentialDirectories = $this->getPotentialDirectories();
		foreach ($potentialDirectories as $directory)
		{
			$sourceFile = $directory . $relativePath;
			if ( is_readable($sourceFile) )
			{
				return $sourceFile;
			}
		}
		return NULL;
	}

	/**
	 * @var Array<String>
	 */
	private $potentialDirectories;
	
	/**
	 * @return void
	 */
	private function resetPotentialDirectories()
	{
		$this->potentialDirectories = array(f_util_FileUtils::buildOverridePath(), f_util_FileUtils::buildProjectPath(), f_util_FileUtils::buildChangeBuildPath());
	}
	
	/**
	 * @param String $directory
	 */
	public function addPotentialDirectory($directory)
	{
		if (!in_array($directory, $this->potentialDirectories))
		{
			array_unshift($this->potentialDirectories, $directory);
		}
	}
	
	/**
	 * @return Array<String>
	 */
	private function getPotentialDirectories()
	{
		return $this->potentialDirectories;
	}
	
	/**
	 * @return void
	 */
	public function addCurrentWebsiteToPotentialDirectories()
	{
		$currentWebsite = website_WebsiteService::getInstance()->getCurrentWebsite();
		if (!is_null($currentWebsite))
		{
			$directory = f_util_FileUtils::buildOverridePath('hostspecificresources', $currentWebsite->getDomain());
			if (is_dir($directory)) 
			{
				$this->addPotentialDirectory($directory);
			}
		}	
	}
	
	/**
	 * Search all path for the file name
	 *
	 * @param string $fileName Full name of the file
	 * @return mixed Null if file not found or array of paths
	 */
	private function resolvePaths($fileName)
	{
		// If package not defined throw exception because you don't know where you must search.
		if (NULL === $this->packageName)
		{
			throw new BadInitializationException('Package name must be defined. Use setPackageName($packageName).');
		}

		// Construct the relative path
		$relativePath = DIRECTORY_SEPARATOR . $this->packageName;
		if (NULL !== $this->directory)
		{
			$relativePath .= DIRECTORY_SEPARATOR . $this->directory;
		}
		$relativePath .= DIRECTORY_SEPARATOR . $fileName;

		$potentialDirectories = $this->getPotentialDirectories();
		$paths = array();
		foreach ($potentialDirectories as $directory)
		{
			$sourceFile = $directory . $relativePath;
			if ( is_readable($sourceFile) )
			{
				$paths[] = $sourceFile;
			}
		}
		if (count($paths) === 0)
		{
			return NULL;
		}
		return $paths;
	}

	/**
	 * @param string $directory
	 * @return string
	 */
	private function cleanPath($directory)
	{
		while ($directory{0} == DIRECTORY_SEPARATOR)
		{
			$directory = substr($directory, 1);
		}
		while (substr($directory, -1) == DIRECTORY_SEPARATOR)
		{
			$directory = substr($directory, 0, -1);
		}
		return $directory;
	}
}


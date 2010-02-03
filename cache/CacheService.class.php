<?php
class CacheService extends BaseService
{
	/**
	 * @var InitDataService
	 */
	private static $instance;

	/**
	 * @return CacheService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	public function clearTemplateCache()
	{
		$directory = f_util_FileUtils::buildChangeCachePath('template');
		if (is_dir($directory))
		{
			$this->deleteRecursively($directory);
		}
	}
	
	public function clearCssCache()
	{
		$cssDir = f_util_FileUtils::buildWebappPath("www", "cache", "css");
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cssDir, RecursiveDirectoryIterator::KEY_AS_PATHNAME), RecursiveIteratorIterator::CHILD_FIRST) as $file => $info)
		{
			if ($info->isFile() && substr($file, -3) == "css")
			{
				touch($file.".deleted");
			}
		}
	}
	
	public function clearAllWebappCache()
	{
		$toClear = array('binding', 'js', 'htmlpreview', 'mediaformat', 'xml');
		foreach ($toClear as $directory)
		{
			$this->clearWebappCache($directory);
		}
		$this->clearCssCache();
		$this->incrementWebappCacheVersion();
	}

	public function clearSimpleCache()
	{
		f_SimpleCache::clear();
	}
	
	/**
	 * Clear all caches containing locales.
	 */
	public function clearLocalizedCache()
	{
		$toClear = array('binding', 'js', 'htmlpreview');
		foreach ($toClear as $directory)
		{
			$this->clearWebappCache($directory);
		}
		$this->clearTemplateCache();
		$this->clearSimpleCache();
	}

	/**
	 * @param String $directory
	 */
	private function clearWebappCache($directory)
	{
		$baseDirectory = f_util_FileUtils::buildWebappPath('www', 'cache');
		if (!is_dir($baseDirectory))
		{
			f_util_FileUtils::mkdir($baseDirectory);
		}

		$directory = f_util_FileUtils::buildWebappPath('www', 'cache', $directory);
		if (is_dir($directory))
		{
			$this->deleteRecursively($directory);
		}
	}

	private function incrementWebappCacheVersion()
	{
		$cacheVersionPath = f_util_FileUtils::buildWebappPath('www', 'cache', 'cacheversion.txt');
		if (is_readable($cacheVersionPath))
		{
			$version = intval(file_get_contents($cacheVersionPath))+1;
		}
		else
		{
			$version = 0;
		}
		file_put_contents($cacheVersionPath, $version);
	}
	
	/**
	 * @param String $directory
	 * @param Boolean $includeDir
	 * @param String $ignoredDir
	 */
	private function deleteRecursively($directory, $includeDir = false, $ignoredDir = null)
	{
		$listOfFiles = scandir($directory);
		foreach ($listOfFiles as $file)
		{
			$absFile = $directory . DIRECTORY_SEPARATOR . $file;
			if (is_dir($absFile))
			{
				if ($file != '.' && $file != '..' && $file != $ignoredDir)
				{
					$this->deleteRecursively($absFile, $includeDir);
				}
			}
			else
			{
				unlink($absFile);
			}
		}
		if ($includeDir)
		{
			rmdir($directory);
		}
	}	
}
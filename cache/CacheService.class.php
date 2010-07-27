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
		$cssDir = f_util_FileUtils::buildWebCachePath("css");
		if (is_dir($cssDir))
		{	
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cssDir, RecursiveDirectoryIterator::KEY_AS_PATHNAME), RecursiveIteratorIterator::CHILD_FIRST) as $file => $info)
			{
				if ($info->isFile() && substr($file, -3) == "css")
				{
					touch($file.".deleted");
				}
			}
		}
	}
	
	public function clearAllWebappCache()
	{
		$toClear = array('binding', 'js', 'htmlpreview', 'mediaformat');
		foreach ($toClear as $directory)
		{
			$this->clearWebCache($directory);
		}
		$this->clearCssCache();
		$this->incrementWebappCacheVersion();
	}

	public function clearSimpleCache()
	{
		//f_SimpleCache::clear();
		f_DataCacheService::getInstance()->clearAll();
	}
	
	/**
	 * Clear all caches containing locales.
	 */
	public function clearLocalizedCache()
	{
		$toClear = array('binding', 'js', 'htmlpreview');
		foreach ($toClear as $directory)
		{
			$this->clearWebCache($directory);
		}
		$this->clearTemplateCache();
		$this->clearSimpleCache();
	}

	/**
	 * @param String $directory
	 */
	private function clearWebCache($directory)
	{
		$directoryPath = f_util_FileUtils::buildWebCachePath($directory);
		if (!is_dir($directoryPath))
		{
			f_util_FileUtils::mkdir($directoryPath);
		}
		else
		{
			$this->deleteRecursively($directoryPath);
		}
	}

	private function incrementWebappCacheVersion()
	{
		$cacheVersionPath = f_util_FileUtils::buildWebCachePath('cacheversion.txt');
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
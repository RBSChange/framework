<?php
/**
 * @method CacheService getInstance()
 */
class CacheService extends change_BaseService
{
	
	public function clearFrontofficeScriptsCache()
	{
		$directory = f_util_FileUtils::buildChangeCachePath('frontofficeScripts');
		if (is_dir($directory))
		{
			$this->deleteRecursively($directory);
		}
	}
	
	public function clearTemplateCache()
	{
		$directory = f_util_FileUtils::buildChangeCachePath('template');
		if (is_dir($directory))
		{
			$this->deleteRecursively($directory);
		}
	}

	public function clearMediaformatCache()
	{
		$directory = f_util_FileUtils::buildChangeCachePath('mediaformat');
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
	
	public function clearJavascriptCache()
	{
		$jsDir = f_util_FileUtils::buildWebCachePath("js");
		if (is_dir($jsDir))
		{	
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($jsDir, RecursiveDirectoryIterator::KEY_AS_PATHNAME), RecursiveIteratorIterator::CHILD_FIRST) as $file => $info)
			{
				if ($info->isFile() && substr($file, -3) == ".js")
				{
					touch($file.".deleted");
				}
			}
		}
	}
	
	public function clearAllWebappCache()
	{
		$toClear = array('binding', 'htmlpreview');
		foreach ($toClear as $directory)
		{
			$this->clearWebCache($directory);
		}
		$this->clearCssCache();
		$this->clearJavascriptCache();
		$this->clearMediaformatCache();
		$this->clearFrontofficeScriptsCache();
		$this->clearBrowscapCache();
		$this->incrementWebappCacheVersion();
	}

	public function clearSimpleCache()
	{
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
	
	
	public function clearBrowscapCache()
	{
		$browscap = new Browscap();
		$browscap->updateCache();
	}
	
	/**
	 * Indicates that the baackoffice interface should be reloaded.
	 */
	public function boShouldBeReloaded()
	{
		$this->incrementWebappCacheVersion();
	}

	/**
	 * @param string $directory
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
		try 
		{
			$this->getTransactionManager()->beginTransaction();
			
			$cacheVersion = $this->getPersistentProvider()->getSettingValue('modules_uixul', 'cacheVersion');
			if ($cacheVersion === null)
			{
				$cacheVersion = 0;
			}
			else
			{
				$cacheVersion = intval($cacheVersion) + 1;
			}
			
			$this->getPersistentProvider()->setSettingValue('modules_uixul', 'cacheVersion', $cacheVersion);
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);	
		}
	}
	
	/**
	 * @param string $directory
	 * @param boolean $includeDir
	 * @param string $ignoredDir
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
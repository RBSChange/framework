<?php
class f_DataCacheFileService extends f_DataCacheService
{
	const INVALID_CACHE_ENTRY = 'invalidCacheEntry';
	
	private static $instance;
	private $registrationFolder = null;
	
	protected function __construct()
	{
		$this->registrationFolder = f_util_FileUtils::buildCachePath('simplecache', 'registration');
		f_util_FileUtils::mkdir($this->registrationFolder);
	}

	/**
	 * @return f_DataCacheFileService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @param String $namespace
	 * @param Mixed $keyParameters
	 * @param String $subCache (optional)
	 * @param Array	$newPatterns
	 * @return f_DataCacheItem or null or String
	 */
	public function readFromCache($namespace, $keyParameters, $newPatterns = null)
	{
		if ($newPatterns !== null)
		{
			$returnItem = true;
		}
		else 
		{
			$returnItem = false;
			$newPatterns = array();
		}
		$item = $this->getNewCacheItem($namespace, $keyParameters, $newPatterns);
		$item->setValidity(true);
		if ($this->exists($item))
		{
			$dirPath = $this->getCachePath($item);
			$subCaches = f_util_FileUtils::getDirFiles($dirPath);
			if (f_util_ArrayUtils::isNotEmpty($subCaches))
			{
				$item->setCreationTime(filemtime($dirPath));
			}
			if ($subCaches != null)
			{
				foreach ($subCaches as $subCache)
				{
					$item->setValue(basename($subCache), f_util_FileUtils::read($subCache));
				}
			}
			return $item;
		}
		return ($returnItem) ? $item : null;
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	public function writeToCache($item)
	{
		f_util_FileUtils::mkdir($this->getCachePath($item));
		$this->register($item);
		$data = $item->getValues();
		$this->markAsBeingRegenerated($item);
		try
		{
			foreach ($data as $k => $v)
			{
				if ($v !== null)
				{
					f_util_FileUtils::unlink($this->getCachePath($item, $k));
					f_util_FileUtils::write($this->getCachePath($item, $k), $v, f_util_FileUtils::OVERRIDE);
				}
			}
		}
		catch (Exception $e)
		{
			// Do not let potential partial or broken content rest on disk
			if ($this->exists($item))
			{
				@f_util_FileUtils::rmdir($this->getCachePath($item));
			}
			throw $e;
		}
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @param String $subCache
	 * @return Boolean
	 */
	public function exists($item, $subCache = null)
	{
		$itemPath = $this->getCachePath($item);
		$cachePath = $this->getCachePath($item, $subCache);
		$result = file_exists($cachePath) && f_util_FileUtils::getDirFiles($itemPath) !== null && $this->isValid($item)
			&& ($item->getTTL() === null || (time() - filemtime($cachePath)) < $item->getTTL()); 
		$this->markAsBeingRegenerated($item);
		return $result;
	}
	
	
	/**
	 * This is the same as BlockCache::commitClear()
	 * but designed for the context of <code>register_shutdown_function()</code>,
	 * to be sure the correct umask is used.
	 */
	public function shutdownCommitClear()
	{
		umask(0002);
		$this->commitClear();
	}
	
	public function cleanExpiredCache()
	{
		$directoryIterator = new DirectoryIterator(f_util_FileUtils::buildChangeCachePath('simplecache'));
		foreach ($directoryIterator as $classNameDir)
		{
			if ($classNameDir->isDir())
			{
				$subDirIterator = new DirectoryIterator($classNameDir->getPathname());
				foreach ($subDirIterator as $cacheKeyDir)
				{
					$invalidCacheFilePath = $cacheKeyDir->getPathname() . DIRECTORY_SEPARATOR . self::INVALID_CACHE_ENTRY;
					if ($cacheKeyDir->isDir() && file_exists($invalidCacheFilePath))
					{
						$fileInfo = new SplFileInfo($invalidCacheFilePath);
						if (abs(date_Calendar::getInstance()->getTimestamp() - $fileInfo->getMTime()) > self::MAX_TIME_LIMIT)
						{
							f_util_FileUtils::rmdir($cacheKeyDir->getPathname());
						}
					}
				}
			}
		}
	}
	
	public function clearCommand()
	{
		f_util_FileUtils::cleanDir(f_util_FileUtils::buildCachePath("simplecache"));
	}
	
	/**
	 * @param String $pattern
	 */
	public function clearCacheByPattern($pattern)
	{
		$cacheIds = $this->getPersistentProvider()->getCacheIdsByPattern($pattern);
		foreach ($cacheIds as $cacheId)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("[". __CLASS__ . "]: clear $cacheId cache");
			}
			$this->clear($cacheId);
		}
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @param String $subCache
	 */
	public final function clearSubCache($item, $subCache)
	{
		$this->registerShutdown();
		$cachePath = $this->getCachePath($item, $subCache);
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' ' . $cachePath);
		}
		if (!array_key_exists($item->getNamespace(), $this->idToClear))
		{
			$this->idToClear[$item->getNamespace()] = array($item->getKeyParameters() => $subCache);
		}
		else if (is_array($this->idToClear[$item->getNamespace()]))
		{
			$this->idToClear[$item->getNamespace()][$item->getKeyParameters()] = $subCache;
		}
	}
	
	protected function commitClear()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("DataCacheFileService->commitClear");
		}
		$cachePath = f_util_FileUtils::buildCachePath('simplecache');
		$dirsToClear = array();
		if ($this->clearAll)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug("Clear all");
			}
			$dirHandler = opendir($cachePath);
			while (($fileName = readdir($dirHandler)) !== false)
			{
				if ($fileName != '.' && $fileName != '..' && $fileName != 'registration' && $fileName != 'old')
				{
					$dirsToClear[] = $cachePath . DIRECTORY_SEPARATOR . $fileName;
				}
			}
			$this->buildInvalidCacheList($dirsToClear);
			closedir($dirHandler);
			if ($this->dispatch)
			{
				f_event_EventManager::dispatchEvent('simpleCacheCleared', null);
			}
		}
		else
		{
			$dispatchParams = array();
			if (!empty($this->idToClear))
			{
				foreach (array_keys($this->idToClear) as $id)
				{
					if (file_exists($cachePath . DIRECTORY_SEPARATOR . $id))
					{
						$dirsToClear[] = $cachePath . DIRECTORY_SEPARATOR . $id;
					}
				}
				$this->buildInvalidCacheList($dirsToClear);
				if ($this->dispatch)
				{
					$dispatchParams["ids"] = $this->idToClear;
				}
			}
			if (!empty($this->docIdToClear))
			{
				$docIdsToClear = array_keys($this->docIdToClear);
				$this->commitClearByDocIds($docIdsToClear);
				if ($this->dispatch)
				{
					$dispatchParams["docIds"] = $this->docIdToClear;
				}
			}
			if ($this->dispatch && count($dispatchParams) > 0)
			{
				f_event_EventManager::dispatchEvent('simpleCacheCleared', null, $dispatchParams);
			}
		}

		$this->clearAll = false;
		$this->idToClear = null;
		$this->docIdToClear = null;
	}

	/**
	 * @param Array $docIds
	 */
	protected function commitClearByDocIds($docIds)
	{
		foreach ($docIds as $id)
		{
			$baseById = $this->registrationFolder.DIRECTORY_SEPARATOR.'byDocId'.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, str_split($id, 3));
			if (!is_dir($baseById))
			{
				continue;
			}
			foreach (scandir($baseById) as $dir)
			{
				if ($dir == '.' || $dir == '..')
				{
					continue;
				}
				$this->putInvalidCacheFlagRecursive($baseById.DIRECTORY_SEPARATOR.$dir);
			}
		}
	}
	
	protected function putInvalidCacheFlagRecursive($dir)
	{
		foreach (scandir($dir) as $subDir)
		{
			if ($subDir == '.' || $subDir == '..')
			{
				continue;
			}
			if (!is_numeric($subDir) && strlen($subDir) > 1)
			{
				@touch($dir.DIRECTORY_SEPARATOR.$subDir.DIRECTORY_SEPARATOR.self::INVALID_CACHE_ENTRY);
			}
			else
			{
				$this->putInvalidCacheFlagRecursive($dir.DIRECTORY_SEPARATOR.$subDir);
			}
		}
	}

	/**
	 * @param Array $dirsToClear
	 */
	protected function buildInvalidCacheList($dirsToClear)
	{
		foreach ($dirsToClear as $dir)
		{
			$dirHandler = opendir($dir);
			while (($fileName = readdir($dirHandler)) !== false)
			{
				if ($fileName != '.' && $fileName != '..')
				{
					$this->putInvalidCacheFlagRecursive($dir.DIRECTORY_SEPARATOR.$fileName);
				}
			}
			closedir($dirHandler);
		}
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @return String
	 */
	public function getCachePath($item, $subCache = null)
	{
		$cachePath = $item->getCachePath();
		if ($cachePath === null)
		{
			$keyParameters = $item->getKeyParameters();
			$cachePath = f_util_FileUtils::buildCachePath('simplecache', $item->getNamespace(), $keyParameters[0], $keyParameters[1], $keyParameters[2], $keyParameters);
			$item->setCachePath($cachePath);
		}
		if ($subCache === null)
		{
			return $cachePath;
		}
		return $cachePath . DIRECTORY_SEPARATOR . $subCache;
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @return Boolean
	 */
	protected function isValid($item)
	{
		return !file_exists($this->getCachePath($item, self::INVALID_CACHE_ENTRY));
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	protected function markAsBeingRegenerated($item)
	{
		if (!$this->isValid($item))
		{
			f_util_FileUtils::unlink($this->getCachePath($item, self::INVALID_CACHE_ENTRY));
		}
	}

	/**
	 * @param f_DataCacheItem $item
	 * @return String
	 */
	protected function getRegistrationPath($item)
	{
		$registrationPath = $item->getRegistrationPath();
		
		if ($registrationPath === null)
		{
			$registrationPath = $this->registrationFolder . DIRECTORY_SEPARATOR . $item->getNamespace();
			$item->setRegistrationPath($registrationPath);
		}
		return $registrationPath;
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	protected function register($item)
	{
		$registrationPath = $this->getRegistrationPath($item);
		if (!file_exists($registrationPath))
		{
			$tm = f_persistentdocument_TransactionManager::getInstance();
			try
			{
				$tm->beginTransaction();
				$pp = f_persistentdocument_PersistentProvider::getInstance();
				$pp->registerSimpleCache($item->getNamespace(), $this->optimizeCacheSpecs($item->getPatterns()));
				$tm->commit();
				@touch($registrationPath);
			}
			catch (Exception $e)
			{
				$tm->rollBack($e);
			}
		}
		$baseById = $this->registrationFolder.DIRECTORY_SEPARATOR.'byDocId';
		
		$keyParams = $item->getKeyParameters();
		foreach ($item->getPatterns() as $spec)
		{
			if (is_numeric($spec))
			{
				$byIdRegister = $baseById.DIRECTORY_SEPARATOR.
					implode(DIRECTORY_SEPARATOR, str_split($spec, 3)).DIRECTORY_SEPARATOR.
					$keyParams[0].DIRECTORY_SEPARATOR.$keyParams[1].DIRECTORY_SEPARATOR.$keyParams[2].
					DIRECTORY_SEPARATOR.$item->getNamespace().'_'.$keyParams;
				if (!file_exists($byIdRegister))
				{
					f_util_FileUtils::mkdir(dirname($byIdRegister));
					f_util_FileUtils::symlink($this->getCachePath($item), $byIdRegister);
				}
			}
		}
	}
}

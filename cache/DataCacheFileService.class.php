<?php
class f_DataCacheFileService extends f_DataCacheService
{
	const INVALID_CACHE_ENTRY = 'invalidCacheEntry';
	
	private $registrationFolder = null;
	
	private function __construct()
	{
		$this->registrationFolder = f_util_FileUtils::buildCachePath('simplecache', 'registration');
		f_util_FileUtils::mkdir($this->registrationFolder);
	}

	/**
	 * @return f_DataCacheService
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
	 * @param Array	$newPatterns
	 * @return f_DataCacheItem or null
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
		
		if ($this->exists($item))
		{
			$subCaches = f_util_FileUtils::getDirFiles($this->getCachePath($item));
			if ($subCaches != null)
			{
				foreach ($subCaches as $subCache)
				{
					$item->setValue(basename($subCache), f_util_FileUtils::read($subCache));
				}
				return $item;
			}
		}
		return ($returnItem) ? $item : null;
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	public function writeToCache(f_DataCacheItem $item)
	{
		$this->register($item);
		$data = $item->getValue();
		try
		{
			foreach ($data as $k => $v)
			{
				if ($k != "creationTime" || $k != "isValid" || $k != "cachePath" || $v !== null)
				{
					f_util_FileUtils::writeAndCreateContainer($this->getCachePath($item, $k), $v, f_util_FileUtils::OVERRIDE);
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
			self::clear($cacheId);
		}
	}
	
	/**
	 * @param String $namespace
	 */
	public function clearCacheByNamespace($namespace)
	{
		
	}

	public function clearAll()
	{
		
	}
	
	private function getCachePath(f_DataCacheItem $item, $subCache = null)
	{
		$cachePath = $item->getValue("cachePath");
		if ($cachePath === null)
		{
			$cachePath = f_util_FileUtils::buildCachePath('simplecache', $item->getNamespace(), $item->getKeyParameters());
			$item->setValue("cachePath", $cachePath);
			f_util_FileUtils::mkdir($cachePath);
		}
		if ($subCache === null)
		{
			return $cachePath;
		}
		return $cachePath . DIRECTORY_SEPARATOR . $subCache;
	}
	
	private function exists(f_DataCacheItem $item, $subCache = null)
	{
		$cachePath = $this->getCachePath($item, $subCache);
		$result = file_exists($cachePath) && $this->isValid($item)
			&& ($item->getTimeLimit() === null || (time() - filemtime($cachePath)) < $item->getTimeLimit()); 
		$this->markAsBeingRegenerated();
		return $result;
	}

	private function isValid(f_DataCacheItem $item)
	{
		return !file_exists($this->getCachePath($item, self::INVALID_CACHE_ENTRY));
	}
	
	private function markAsBeingRegenerated(f_DataCacheItem $item)
	{
		if (!$this->isValid($item))
		{
			f_util_FileUtils::unlink($this->getCachePath($item, self::INVALID_CACHE_ENTRY));
		}
	}

	private function getRegistrationPath(f_DataCacheItem $item)
	{
		$registrationPath = $item->getValue("registrationPath");
		
		if ($registrationPath === null)
		{
			$registrationPath = $this->registrationFolder . DIRECTORY_SEPARATOR . $item->getNamespace();
			$item->setValue("registrationPath", $registrationPath);
		}
		return $registrationPath;
	}
	
	private function register(f_DataCacheItem $item)
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
		$baseById = f_util_FileUtils::buildCachePath($this->registrationFolder, 'byDocId');

		foreach ($item->getPatterns() as $spec)
		{
			if (is_numeric($spec))
			{
				$byIdRegister = $baseById . implode(DIRECTORY_SEPARATOR, str_split($spec, 3)) . DIRECTORY_SEPARATOR;
				$byIdRegister .= $item->getNamespace() . '_' . $item->getKeyParameters();
				if (!file_exists($byIdRegister))
				{
					f_util_FileUtils::mkdir(dirname($byIdRegister));
					f_util_FileUtils::symlink($this->getCachePath($item), $byIdRegister);
				}
			}
		}
	}
}
?>
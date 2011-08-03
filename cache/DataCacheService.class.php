<?php
interface f_DataCacheItem
{
	/**
	 * @return Integer (timestamp) or null
	 */
	public function getCreationTime();
	
	/**
	 * @param Integer (timestamp) $timestamp
	 */
	public function setCreationTime($timestamp);

	/**
	 * @param Integer $seconds
	 */
	public function setTTL($seconds);
	
	/**
	 * @return Integer
	 */
	public function getTTL();

	/**
	 * @param Mixed $key
	 * @param Mixed (serializable) $value
	 */
	public function setValue($key, $value);
	
	/**
	 * @param String $key
	 * @return Mixed
	 */
	public function getValue($key);
	
	/**
	 * @param Array $key
	 */
	public function setValues($key);
	
	/**
	 * @return Array
	 */
	public function getValues();
	
	/**
	 * @param String $key
	 */
	public function setRegistrationPath($key);
	
	/**
	 * @return String
	 */
	public function getRegistrationPath();
	
	/**
	 * @param String $key
	 */
	public function setCachePath($key);
	
	/**
	 * @return String
	 */
	public function getCachePath();
	
	/**
	 * @return String
	 */
	public function getNamespace();
	
	/**
	 * @return String
	 */
	public function getKeyParameters();
	
	/**
	 * @return Array
	 */
	public function getPatterns();
	
	public function setInvalid();
	
	/**
	 * @param Boolean $isValid
	 */
	public function setValidity($isValid);
	
	/**
	 * @return Boolean
	 */
	public function isValid();
}

class f_DataCacheItemImpl implements f_DataCacheItem
{
	const MAX_TIME_LIMIT = 86400;
	
	private $namespace;
	private $keyParameters;
	private $patterns;
	private $timeLimit;
	private $creationTime = null;
	private $isValid = false;
	private $registrationPath = null;
	private $cachePath = null;
	private $regenerated = false;
	private $isNew = false;
	private $data;
		
	/**
	 * @param String $namespace
	 * @param Mixed $keyParameters
	 * @param Array $patterns
	 */
	public function __construct($namespace, $keyParameters, $patterns)
	{
		$this->namespace = str_replace(':', '_', $namespace);
		$this->keyParameters = md5(serialize($keyParameters));
		$this->patterns = $patterns;
		
		$this->setTTL(self::MAX_TIME_LIMIT);
		foreach ($patterns as $pattern)
		{
			if (f_util_StringUtils::beginsWith($pattern, "ttl/"))
			{
				$this->setTTL(intval(substr($pattern, 4)));
				continue;	
			}
			if (f_util_StringUtils::beginsWith($pattern, "time:"))
			{
				$this->setTTL(intval(substr($pattern, 5)));	
			}
		}
		
		$this->data = array();
	}
	
	/**
	 * @see f_DataCacheItem::getCreationTime()
	 *
	 * @return Integer (timestamp) or null
	 */
	public function getCreationTime()
	{
		if ($this->creationTime !== null)
		{
			return $this->creationTime;
		}
		return null;
	}
	
	/**
	 * @return String
	 */
	public function getNamespace()
	{
		return $this->namespace;
	}
	
	/**
	 * @return String
	 */
	public function getKeyParameters()
	{
		return $this->keyParameters;
	}
	
	/**
	 * @return Integer
	 */
	public function getTTL()
	{
		return $this->timeLimit;
	}
	
	/**
	 * @return Array
	 */
	public function getPatterns()
	{
		return $this->patterns;
	}
	
	/**
	 * @see f_DataCacheItem::getValue()
	 *
	 * @param String $key
	 * @return Mixed
	 */
	public function getValue($key)
	{
		if (isset($this->data[$key]) && $this->data[$key] != null)
		{
			return $this->data[$key];
		}
		return null;
	}
	
	/**
	 * @see f_DataCacheItem::getValues()
	 *
	 * @return Array
	 */
	public function getValues()
	{
		return $this->data;
	}
	
	/**
	 * @see f_DataCacheItem::setTTL()
	 *
	 * @param Integer $seconds
	 */
	public function setTTL($seconds)
	{
		if ($seconds < self::MAX_TIME_LIMIT && $seconds > 0)
		{
			$this->timeLimit = $seconds;
		}
		else 
		{
			$this->timeLimit = self::MAX_TIME_LIMIT;
		}
	}
	
	/**
	 * @see f_DataCacheItem::setCreationTime()
	 *
	 * @param Integer $timestamp
	 */
	public function setCreationTime($timestamp)
	{
		$this->creationTime = $timestamp;
	}
	
	public function setRegistrationPath($path)
	{
		$this->registrationPath = $path;
	}
	
	public function getRegistrationPath()
	{
		return $this->registrationPath;
	}
	
	public function setCachePath($path)
	{
		$this->cachePath = $path;
	}
	
	public function getCachePath()
	{
		return $this->cachePath;
	}
	
	/**
	 * @see f_DataCacheItem::setValue()
	 *
	 * @param String $key
	 * @param Mixed $value
	 */
	public function setValue($key, $value)
	{
		$this->data[$key] = $value;
	}
	
	public function setValues($values)
	{
		$this->data = $values;
	}
	
	public function setInvalid()
	{
		$this->isValid = false;
	}
	
	/**
	 * @param Boolean $isValid
	 */
	public function setValidity($isValid)
	{
		$this->isValid = $isValid;
	}
	
	/**
	 * @return Boolean
	 */
	public function isValid()
	{
		return $this->isValid && $this->getCreationTime() !== null && ($this->getCreationTime()+$this->timeLimit > time());
	}
	
	public function markAsNew()
	{
		$this->isNew = true;
	}
	
	public function isNew()
	{
		return $this->isNew;
	}
}

class f_DataCacheService extends BaseService
{
	const MAX_TIME_LIMIT = 86400;
	
	/**
	 * @var f_DataCacheService
	 */
	private static $instance;
	
	protected $clearAll = false;
	protected $idToClear = array();
	protected $docIdToClear = array();
	protected $dispatch = true;
	protected $shutdownRegistered = false;

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
	 * @param Array $patterns
	 * @return f_DataCacheItem
	 */
	public function getNewCacheItem($namespace, $keyParameters, $patterns)
	{
		return new f_DataCacheItemImpl($namespace, $keyParameters, $patterns);
	}
	
	/**
	 * @return Boolean
	 */
	public function isEnabled()
	{
		return DISABLE_DATACACHE !== true;
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
		
		$item = $this->getData($this->getNewCacheItem($namespace, $keyParameters, $newPatterns));
		if ($returnItem || $this->exists($item))
		{
			return $item;
		}
		
		return null;
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	public function writeToCache($item)
	{
		$item->setValidity(false);
	}
	
	/**
	 * @param f_DataCacheItem $item
	 */
	public function markAsBeingRegenerated($item)
	{
		// nothing
	}
	
	/**
	 * @param f_DataCacheItem $item
	 * @param String $subCache
	 * @return Boolean
	 */
	public function exists($item, $subCache = null)
	{
		$result = $item->isValid();
		if ($subCache !== null)
		{
			return $result && $item->getValue($subCache) !== null;
		}
		return $result;
	}
	
	/**
	 * @param String $pattern
	 */
	public function clearCacheByPattern($pattern)
	{
		$cacheIds = $this->getCacheIdsForPattern($pattern);
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
	 * Has to be implemeted by sub class
	 * @param string $pattern
	 * @return array
	 */
	public function getCacheIdsForPattern($pattern)
	{
		return array();
	}
	
	/**
	 * @param String $namespace
	 */
	public function clearCacheByNamespace($namespace)
	{
		$this->clear($namespace);
	}
	
	/**
	 * @param String $id
	 */
	public function clearCacheByDocId($id)
	{
		$this->registerShutdown();
		$this->docIdToClear[$id] = true;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 */
	public function clearCacheByModel($model)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("[". __CLASS__ . "]: clear cache by model:".$model->getName());
		}
		$this->clearCacheByPattern($model->getName());
	}

	/**
	 * @param String $tag
	 */
	public function clearCacheByTag($tag)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("[". __CLASS__ . "]: clear cache by tag:$tag");
		}
		$this->clearCacheByPattern(f_DataCachePatternHelper::getTagPattern($tag));
	}

	public function clearAll()
	{
		$this->clear();
	}
	
	public function cleanExpiredCache()
	{
		return true;
	}
	
	/**
	 * This is the same as BlockCache::commitClear()
	 * but designed for the context of <code>register_shutdown_function()</code>,
	 * to be sure the correct umask is used.
	 */
	public function shutdownCommitClear()
	{
		$this->commitClear();
	}
	
	protected function commitClear()
	{
		return true;
	}
	
	public function clearCommand()
	{
		return true;
	}
	
	/**
	 * @param Array $cacheSpecs
	 * @return Array
	 */
	protected function optimizeCacheSpecs($cacheSpecs)
	{
		if (f_util_ArrayUtils::isNotEmpty($cacheSpecs))
		{
			$finalCacheSpecs = array();
			foreach (array_unique($cacheSpecs) as $spec)
			{
				if (preg_match('/^modules_\w+\/\w+$/', $spec))
				{
					try
					{
						$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($spec);
						$finalCacheSpecs[] = $model->getName();
					}
					catch (Exception $e)
					{
						Framework::exception($e);
					}
				}
				else if (strpos($spec, 'tags/') === 0 && strpos($spec, '*') !== false)
				{
					try
					{
						$tags = TagService::getInstance()->getAvailableTagsByPattern(substr($spec,5));
						foreach ($tags as $tag)
						{
							$finalCacheSpecs[] = 'tags/' . $tag;
						}
					}
					catch (Exception $e)
					{
						Framework::exception($e);
					}
				}
				elseif (!is_numeric($spec))
				{
					$finalCacheSpecs[] = $spec;
				}
			}

			return $finalCacheSpecs;
		}
		return array();
	}
	
	protected function registerShutdown()
	{
		if (!$this->shutdownRegistered)
		{
			register_shutdown_function(array($this,'shutdownCommitClear'));
			$this->shutdownRegistered = true;
		}
	}
	
	/**
	 * @param String $id
	 */
	protected function clear($id = null)
	{
		$this->registerShutdown();
		if ($id === null)
		{
			$this->clearAll = true;
		}
		else
		{
			$this->idToClear[$id] = true;
		}
	}
	
	/**
	 * @param array $patternArray
	 * @param array $idArray
	 */
	public function commitClearDispatched($patternArray,  $idArray)
	{
		$this->idToClear = $patternArray;
		$this->docIdToClear = $idArray;
		$this->commitClear();
	}
	
	/**
	 */
	public function clearAllDispatched()
	{
		$this->clearAll = true;
		$this->commitClear();
	}

	/**
	 * Has to be implemeted by sub class
	 * @param f_DataCacheItem $item
	 * @return f_DataCacheItem
	 */
	protected function getData($item)
	{
		$item->setValidity(false);
		return $item;
	}
	/**
	 * @param Boolean $dispatch
	 */
	public function setDispatch($dispatch = true)
	{
		$this->dispatch = $dispatch;
	}
}
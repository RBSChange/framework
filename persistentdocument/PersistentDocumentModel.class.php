<?php
/**
 * @deprecated
 */
abstract class f_persistentdocument_PersistentDocumentModel extends \Change\Documents\AbstractModel implements f_mvc_BeanModel 
{
	/**
	 * @deprecated
	 */
	const PRIMARY_KEY_ID = "id";
	
	/**
	 * @deprecated
	 */
	protected  $m_childrenProperties;
	
	/**
	 * @return NULL
	 */
	public function getVendorName()
	{
		return null;
	}
	
	/**
	 * @deprecated
	 */
	protected  $m_preservedPropertiesNames = array();
	
	/**
	 * @deprecated
	 */
	public function getPreservedPropertiesNames()
	{
		return $this->m_preservedPropertiesNames;
	}
	
	/**
	 * @deprecated
	 */
	public function isPreservedProperty($name)
	{
		return isset($this->m_preservedPropertiesNames[$name]);
	}
	
	/**
	 * @deprecated
	 */
	public static function buildDocumentModelName($moduleName, $documentName)
	{
		return 'modules_' . $moduleName . '/' .$documentName;
	}

	/**
	 * @deprecated
	 */
	public static function convertModelNameToBackoffice($modelName)
	{
		return str_replace('/', '_', $modelName);
	}
	
	/**
	 * @deprecated
	 */
	public static function getModelInfo($modelName)
	{
		$matches = null;
		if (preg_match('#^modules_(.*)/(.*)$#', $modelName, $matches))
		{
			return array("module" => $matches[1], "document" => $matches[2]);
		}
		throw new Exception("Invalid model name $modelName");
	}
	
	/**
	 * @var deprecated
	 */
	protected static $documentModels = array();

	/**
	 * @deprecated
	 */
	public static function getInstanceFromDocumentModelName($modelName)
	{
		if (!array_key_exists($modelName, self::$documentModels))
		{
			$className = self::getModelClassName($modelName);
			if ($className)
			{
				self::$documentModels[$modelName] = new $className();
			}
			else
			{
				self::$documentModels[$modelName] = null;
			}
		}
		$model =  self::$documentModels[$modelName];
		if ($model === null)
		{
			throw new BaseException("type_must_be_a_module");
		}
		return $model;
	}
	
	/**
	 * @deprecated
	 */
	protected static function getModelClassName($modelName)
	{
		list ($package, $documentName) = explode('/', $modelName);
		list ($packageType, $moduleName) = explode('_', $package);
		if ($packageType != 'modules' || empty($moduleName) || empty($documentName))
		{
			return null;
		}
		$className = $moduleName .'_persistentdocument_'.$documentName.'model';
		if (class_exists($className))
		{
			return $className;
		}
		return null;
	}
	
	/**
	 * @deprecated
	 */
	public static function documentModelNameToDocumentClassName($documentModelName)
	{
		return self::getInstanceFromDocumentModelName($documentModelName)->getDocumentClassName();
	}

	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @return string
	 */
	public static function composeModelName($moduleName, $documentName)
	{
		return 'modules_' . $moduleName . '/' .$documentName;
	}
	
	/**
	 * @deprecated
	 */
	public static function getInstance($moduleName, $documentName)
	{
		$modelName = self::composeModelName($moduleName, $documentName);
		return self::getInstanceFromDocumentModelName($modelName);
	}

	/**
	 * @deprecated
	 */
	static function getNewModelInstance($moduleName, $documentName)
	{
		$modelName = self::composeModelName($moduleName, $documentName);
		return self::getInstanceFromDocumentModelName($modelName);
	}

	/**
	 * @deprecated
	 */
	public static function exists($documentModelName)
	{
		$model = self::getModelClassName($documentModelName);
		return $model != null;
	}

	/**
	 * @deprecated
	 */
	public static function getDocumentModels()
	{
		self::$documentModels = array();
		foreach (self::getDocumentModelNamesByModules() as $modelNames)
		{
			foreach ($modelNames as $modelName)
			{
				self::getInstanceFromDocumentModelName($modelName);
			}
		}
		return self::$documentModels;
	}
	
	/**
	 * @deprecated
	 */
	public static function getDocumentModelNamesByModules()
	{
		return unserialize(file_get_contents(f_util_FileUtils::buildChangeBuildPath('documentmodels.php')));
	}
	
	/**
	 * @deprecated
	 */
	public static function getModelChildrenNames($modelName = null)
	{
		$modelChildren = unserialize(file_get_contents(f_util_FileUtils::buildChangeBuildPath('documentmodelschildren.php')));
		if ($modelName === null)
		{
			return $modelChildren;
		}
		
		if (isset($modelChildren[$modelName]))
		{
			return $modelChildren[$modelName];
		}	
		
		return array();
	}

	/**********************************************************/
	/* Document Status Informations							*/
	/**********************************************************/

	/**
	 * @deprecated
	 */
	public final function getBackofficeName()
	{
		return self::convertModelNameToBackoffice($this->getName());
	}

	/**
	 * @deprecated
	 */
	public final function getStatuses()
	{
		return array('DRAFT','CORRECTION','ACTIVE','PUBLISHED','DEACTIVATED','FILED','DEPRECATED','TRASH','WORKFLOW');
	}

	/**
	 * @param string $status
	 * @return boolean
	 */
	public final function hasSatutsCode($status)
	{
		return in_array($status, $this->getStatuses());
	}
	
	/**
	 * @deprecated
	 */
	public static function getSystemProperties()
	{
		return array('id', 'model', 'author', 'authorid', 'creationdate','modificationdate','publicationstatus',
				'lang','metastring','modelversion','documentversion', 'si18n');
	}
	
	
	/**
	 * @deprecated
	 */
	public function getBaseName()
	{
		return $this->getParentName();
	}	
	
	/**
	 * @deprecated
	 */
	public function getTableName()
	{
		return str_replace(array('modules_', '/'), array('m_', '_doc_'), strtolower($this->getRootModelName()));
	}
	
	/**
	 * @deprecated
	 */
	public function getDocumentClassName()
	{
		return $this->getModuleName()."_persistentdocument_".$this->getDocumentName();
	}
	
	/**
	 * @return f_persistentdocument_DocumentService
	 */
	public function getDocumentService()
	{
		$className = $this->getModuleName(). "_"  . ucfirst($this->getDocumentName()) . 'Service';
		return call_user_func(array($className, 'getInstance'));
	}
	
	/**
	 * @deprecated
	 */
	public function getDefaultNewInstanceStatus()
	{
		return $this->getDefaultStatus();
	}
	
	/**
	 * @deprecated
	 */
	public function getFilePath()
	{
		return __FILE__;
	}
	
	/**
	 * @deprecated
	 */
	protected function loadChildrenProperties()
	{
		$this->m_childrenProperties = array();
	}
	
	/**
	 * @deprecated
	 */
	public final function getChildrenPropertiesInfos()
	{
		if ($this->m_childrenProperties === null) {$this->loadChildrenProperties();}
		return $this->m_childrenProperties;
	}
	
	
	/**
	 * @deprecated
	 */
	public final function getChildProperty($propertyName)
	{
		if ($this->m_childrenProperties === null) {$this->loadChildrenProperties();}
		if (isset($this->m_childrenProperties[$propertyName]))
		{
			return $this->m_childrenProperties[$propertyName];
		}
		return null;
	}
	
	/**
	 * @deprecated
	 */
	public final function isChildValidType($modelName)
	{
		if ($this->m_childrenProperties === null) {$this->loadChildrenProperties();}
		foreach ($this->m_childrenProperties as $childProperty)
		{
			if ($childProperty->getType() == $modelName || $childProperty->getType() == '*')
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * @deprecated
	 */
	private $beanPropertiesInfo;	
	
	/**
	 * @deprecated
	 */
	function getBeanName()
	{
		return $this->getDocumentName();
	}
	
	/**
	 * @deprecated
	 */
	function getBeanPropertiesInfos()
	{
		if ($this->beanPropertiesInfo === null)
		{
			$this->loadBeanProperties();
		}
		return $this->beanPropertiesInfo;
	}
	
	/**
	 * @deprecated
	 */
	function getBeanPropertyInfo($propertyName)
	{
		if ($this->beanPropertiesInfo === null)
		{
			$this->loadBeanProperties();
		}
		if (isset($this->beanPropertiesInfo[$propertyName]))
		{
			return $this->beanPropertiesInfo[$propertyName];
		}
		throw new Exception("property $propertyName does not exists!");
	}
	
	/**
	 * @deprecated
	 */	
	private function loadBeanProperties()
	{
		$this->beanPropertiesInfo = array();
		foreach ($this->getEditablePropertiesInfos() as $propertyName => $propertyInfo)
		{
			if ($propertyName == "model")
			{
				continue;
			}
			$this->beanPropertiesInfo[$propertyName] = new f_persistentdocument_PersistentDocumentBeanPropertyInfo($this->getModuleName(), $this->getDocumentName(), $propertyInfo);
		}
	}
	
	/**
	 * @deprecated
	 */
	function hasBeanProperty($propertyName)
	{
		if ($this->beanPropertiesInfo === null)
		{
			$this->loadBeanProperties();
		}
		return isset($this->beanPropertiesInfo[$propertyName]);
	}
	
	/**
	 * @deprecated
	 */
	public function getBeanConstraints()
	{
		// empty. TODO: fill it during documents compilation process
	}

	/**
	 * @deprecated
	 */
	public function __call($name, $arguments)
	{
		switch ($name)
		{
			case 'getFormProperty': 
				Framework::error('Call to deleted ' . get_class($this) . '->'. $name .' method');
				return null;
				
			case 'getFormPropertiesInfos':
				Framework::error('Call to deleted ' . get_class($this)  . '->'. $name .' method');
				return array();
				
			case 'isDocumentIdPrimaryKey':
				Framework::error('Call to deleted ' . get_class($this)  . '->'. $name .' method');
				return true;				
			case 'getPrimaryKey':
				Framework::error('Call to deleted ' . get_class($this)  . '->'. $name .' method');
				return array('id');
			default: 
				throw new BadMethodCallException('No method ' . get_class($this) . '->' . $name);
		}
	}
}
<?php
/**
 * @package framework.persistentdocument
 * f_persistentdocument_PersistentDocumentModel
 */
abstract class f_persistentdocument_PersistentDocumentModel implements f_mvc_BeanModel 
{
	private static $publicationStatusArray = array('DRAFT','CORRECTION','ACTIVE','PUBLICATED','DEACTIVATED','FILED','DEPRECATED','TRASH','WORKFLOW');
	/**
	 * @var array<BeanPropertyInfo>
	 */
	private $beanPropertiesInfo;
	
	private static $m_documentModels;

	/**
	 * @var array<PropertyInfo>
	 */
	protected  $m_properties;
	protected  $m_invertProperties;
	protected  $m_serialisedproperties;
	protected  $m_childrenProperties;
	
	protected  $m_propertiesNames;
	protected  $m_preservedPropertiesNames;
	
	/**
	 * @var String[]
	 */
	protected  $m_childrenNames;
	
	/**
	 * @var String
	 */
	protected  $m_parentName;

	const PRIMARY_KEY_ID = "id";
	
	const BASE_MODEL = 'modules_generic/Document';

	/**
	 * @param String $moduleName
	 * @param String $documentName
	 * @return String
	 */
	public static function buildDocumentModelName($moduleName, $documentName)
	{
		return "modules_$moduleName/$documentName";
	}

	/**
	 * @param String $modelName
	 * @return String
	 */
	public static function convertModelNameToBackoffice($modelName)
	{
		return str_replace('/', '_', $modelName);
	}
	
	/**
	 * @param String $modelName modules_<module>/<document>
	 * @return array<String, String> keys module & document
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
	 * Get instance from complet document model name
	 * @param string $documentModelName Ex : modules_<generic>/<folder>
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	public static function getInstanceFromDocumentModelName($documentModelName)
	{
		list ($package, $docName) = explode('/', $documentModelName);
		list ($packageType, $packageName) = explode('_', $package);
		if ($packageType != 'modules')
		{
			throw new BaseException("type_must_be_a_module");
		}

		return  self::getInstance($packageName, $docName);
	}
	
	/**
	 * @param String $documentModelName For example: "modules_mymodule/mydocument"
	 * @return String the corresponding document class name For example: mymodule_persistentdocument_mydocument
	 */
	public static function documentModelNameToDocumentClassName($documentModelName)
	{
		return self::getInstanceFromDocumentModelName($documentModelName)->getDocumentClassName();
	}

	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	public static function getInstance($moduleName, $documentName)
	{
		// TODO: this is too ugly "old fashioned"...
		if (empty($moduleName))
		{
			throw new BaseException("module-name-cannot-be-empty");
		}
		if (empty($documentName))
		{
			throw new BaseException("module-type-cannot-be-empty");
		}

		$documentModelName = self::buildDocumentModelName($moduleName, $documentName);

		if (self::$m_documentModels == null)
		{
			self::$m_documentModels = array();
		}

		if (!isset(self::$m_documentModels[$documentModelName]))
		{
			$modulesConf = Framework::getConfiguration("injection");
			$documentsInjectionConf = isset($modulesConf['document']) ? $modulesConf['document'] : null;
			
			if ($documentsInjectionConf !== null && (($key = array_search($moduleName."/".$documentName, $documentsInjectionConf)) !== false))
			{
				// We requested a model that injects => instantiate "original" model (just the name of the class is "original". Properties are from the model that injects) 
				list($injectedModuleName, $injectedDocumentName) = explode("/", $key);
				$model = self::getNewModelInstance($injectedModuleName, $injectedDocumentName);
			}
			else
			{
				$model = self::getNewModelInstance($moduleName, $documentName);	
			}			
			
			self::$m_documentModels[$documentModelName] = $model;
		}
		return self::$m_documentModels[$documentModelName];
	}

	/**
	 * @param String $moduleName
	 * @param String $documentName
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	static function getNewModelInstance($moduleName, $documentName)
	{
		$className = self::getClassNameFromDocument($moduleName, $documentName);
		if (!f_util_ClassUtils::classExists($className))
		{
			if ($moduleName != 'generic' && $documentName == 'folder')
			{
				Framework::info('Using generic folder');
				$className = 'generic_persistentdocument_foldermodel';
			}
			else
			{
				throw new Exception("Unknown document model $className.");
			}
		}
		return new $className;
	}

	/**
	 * @param String $documentModelName
	 * @return Boolean
	 */
	public static function exists($documentModelName)
	{
		list ($package, $docName) = explode('/', $documentModelName);
		list ($packageType, $packageName) = explode('_', $package);
		if ($packageType != 'modules')
		{
			throw new BaseException("type_must_be_a_module");
		}
		
		return f_util_ClassUtils::classExists(self::getClassNameFromDocument($packageName, $docName));
	}

	private static function getClassNameFromDocument($moduleName, $documentName)
	{
		return $moduleName .'_persistentdocument_'.$documentName.'model';
	}
	
	/**
	 * @return array<f_persistentdocument_PersistentDocumentModel>
	 */
	public static function getDocumentModels()
	{
		$documentModels = array();
		foreach (self::getDocumentModelNamesByModules() as $modelNames)
		{
			foreach ($modelNames as $modelName)
			{
				$documentModels[$modelName] = self::getInstanceFromDocumentModelName($modelName);
			}
		}
		return $documentModels;
	}
	/**
	 * returns an array of the type : array('moduleA' => array('modules_moduleA/doc1', ...), ...);
	 *
	 * @return array
	 */
	public static function getDocumentModelNamesByModules()
	{
		return unserialize(file_get_contents(f_util_FileUtils::buildChangeBuildPath('documentmodels.php')));
	}
	
	private static $modelChildren;
	/**
	 * If no child is available for model, key does not exists in returned array
	 * @return array array('modules_moduleA/doc1' => array('modules_moduleA/doc2', ...), ...)
	 */
	public static function getModelChildrenNames($modelName = null)
	{
		if (self::$modelChildren === null)
		{
			self::$modelChildren = unserialize(file_get_contents(f_util_FileUtils::buildChangeBuildPath('documentmodelschildren.php')));	
		}
		if ($modelName === null)
		{
			return self::$modelChildren;	
		}
		if (isset(self::$modelChildren[$modelName]))
		{
			return self::$modelChildren[$modelName];
		}
		return array();
	}

	protected function __construct()
	{
	}
	
	/**
	 * @return String
	 */
	abstract public function getFilePath();

	/**
	 * @return String
	 */
	abstract public function getIcon();

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return LocaleService::getInstance()->transFO($this->getLabelKey());
	}

	/**
	 * @return string
	 */
	abstract function getLabelKey();

	/**
	 * @return string For example: modules_generic/folder
	 */
	abstract public function getName();

	/**
	 * @return string|NULL For example: modules_generic/folder or null
	 */
	abstract public function getBaseName();

	/**
	 * @return string For example: generic
	 */
	abstract public function getModuleName();

	/**
	 * @return string For example: folder
	 */
	abstract public function getDocumentName();

	/**
	 * @return string
	 */
	abstract public function getTableName();

	/**
	 * @return boolean
	 */
	abstract public function isLocalized();
	
	/**
	 * @return string[]|NULL
	 */
	public function getChildrenNames()
	{
		return $this->m_childrenNames;
	}
	
	/**
	 * @return boolean
	 */
	function hasChildren()
	{
		return $this->m_childrenNames !== null;
	}
	
	/**
	 * @return string
	 */
	function getParentName()
	{
		return $this->m_parentName;
	}
	
	/**
	 * @return string
	 */
	function getDocumentClassName()
	{
		return $this->getModuleName()."_persistentdocument_".$this->getDocumentName();
	}
	
	/**
	 * @return boolean
	 */
	function hasParent()
	{
		return $this->m_parentName !== null;
	}

	/**
	 * @return boolean
	 */
	abstract public function isIndexable();
	
	/**
	 * @return boolean
	 */
	public function isBackofficeIndexable()
	{
		return false;
	}
	
	/**
	 * @return string[]
	 */
	abstract public function getAncestorModelNames();
	
	/**
	 * @param string $modelName
	 * @return boolean
	 */
	public final function isModelCompatible($modelName)
	{
		switch ($modelName)
		{
			case 'modules_generic/Document':
			case $this->getName():
				return true;			
			default: 
				return in_array($modelName, $this->getAncestorModelNames());
		}
	}

	/**********************************************************/
	/* Document Status Informations                            */
	/**********************************************************/

	/**
	 * @return string Convert model name from 'modules_generic/folder' to 'modules_generic_folder'
	 */
	public final function getBackofficeName()
	{
		return self::convertModelNameToBackoffice($this->getName());
	}

	/**
	 * @return string[] 'DRAFT','CORRECTION','ACTIVE','PUBLICATED','DEACTIVATED','FILED','DEPRECATED','TRASH','WORKFLOW'
	 */
	public final function getStatuses()
	{
		return self::$publicationStatusArray;
	}

	/**
	 * @param string $status
	 * @return boolean
	 */
	public final function hasSatutsCode($status)
	{
		return in_array($status, self::$publicationStatusArray);
	}

	/**
	 * @return string
	 */
	abstract public function getDefaultNewInstanceStatus();


	/**********************************************************/
	/* Properties Informations                                */
	/**********************************************************/
	
	protected function loadProperties()
	{
		$this->m_properties = array();
	}
	
	/**
	 * @return array<String, PropertyInfo> ie. <propName, propertyInfo> 
	 */
	public final function getPropertiesInfos()
	{
		if ($this->m_properties === null){$this->loadProperties();}
		return $this->m_properties;
	}
	
	/**
	 * @var array
	 */
	private static $systemProperties;
	
	/**
	 * @return string[]
	 */
	public static function getSystemProperties()
	{
		if (self::$systemProperties === null)
		{
			self::$systemProperties = array('id', 'model', 'author', 'authorid',
				'creationdate','modificationdate','publicationstatus',
				'lang','metastring','modelversion','documentversion');
		}
		return self::$systemProperties;
	}
	
	public final function getVisiblePropertiesInfos()
	{
		return array_diff_key($this->getEditablePropertiesInfos(), array_flip(self::getSystemProperties()));
	}

	/**
	 * @param string $propertyName
	 * @return PropertyInfo
	 */
	public final function getProperty($propertyName)
	{
		if ($this->m_properties === null){$this->loadProperties();}
		if (isset($this->m_properties[$propertyName]))
		{
			return $this->m_properties[$propertyName];
		}
		return null;
	}
	
	protected function loadSerialisedProperties()
	{
		$this->m_serialisedproperties = array();
	}
	
	/**
	 * @return array<String, PropertyInfo> ie. <propName, propertyInfo> 
	 */
	public final function getSerializedPropertiesInfos()
	{
		if ($this->m_serialisedproperties === null) {$this->loadSerialisedProperties();}
		return $this->m_serialisedproperties;
	}
	
	/**
	 * @param string $propertyName
	 * @return PropertyInfo
	 */	
	public final function getSerializedProperty($propertyName)
	{
		if ($this->m_serialisedproperties === null) {$this->loadSerialisedProperties();}
		if (isset($this->m_serialisedproperties[$propertyName]))
		{
			return $this->m_serialisedproperties[$propertyName];
		}
		return null;
	}	
	
	/**
	 * @return array<String, PropertyInfo> ie. <propName, propertyInfo> 
	 */	
	public final function getEditablePropertiesInfos()
	{
		if ($this->m_properties === null){$this->loadProperties();}
		if ($this->m_serialisedproperties === null) {$this->loadSerialisedProperties();}
		return array_merge($this->m_properties, $this->m_serialisedproperties);
	}	
		

	/**
	 * @param string $propertyName
	 * @return PropertyInfo
	 */	
	public final function getEditableProperty($propertyName)
	{
		if ($this->m_properties === null){$this->loadProperties();}
		if (isset($this->m_properties[$propertyName]))
		{
			return $this->m_properties[$propertyName];
		} 
		
	
		if ($this->m_serialisedproperties === null) {$this->loadSerialisedProperties();}
		if (isset($this->m_serialisedproperties[$propertyName]))
		{
			return $this->m_serialisedproperties[$propertyName];
		}
		
		return null;
	}	

	/**
	 * @return PropertyInfo[]
	 */
	public final function getIndexedPropertiesInfos()
	{
		$result = array();
		foreach ($this->getEditablePropertiesInfos() as $propertyName => $property) 
		{
			/* @var $property PropertyInfo */
			if ($property->isIndexed())
			{
				$result[$propertyName] = $property;
			}
		}
		return $result;
	}

	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isTreeNodeProperty($propertyName)
	{
		$property = $this->getProperty($propertyName);
		return is_null($property) ? false : $property->isTreeNode();
	}

	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isDocumentProperty($propertyName)
	{
		$property = $this->getProperty($propertyName);
		return is_null($property) ? false : $property->isDocument();
	}

	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isArrayProperty($propertyName)
	{
		$property = $this->getProperty($propertyName);
		return is_null($property) ? false : $property->isArray();
	}

	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isUniqueProperty($propertyName)
	{
		$property = $this->getProperty($propertyName);
		return is_null($property) ? false : $property->isUnique();
	}

	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isProperty($propertyName)
	{
		$property = $this->getProperty($propertyName);
		return is_null($property) ? false : true;
	}

	/**
	 * @return array<string>
	 */
	public function getPropertiesNames()
	{
		if ($this->m_propertiesNames === null)
		{
			$this->m_propertiesNames = array();
			foreach ($this->getPropertiesInfos() as $name => $infos)
			{
				if ($name != 'id' && $name != 'model')
				{
					$this->m_propertiesNames[] = $name;
				}
			}
		}
		return $this->m_propertiesNames;
	}

	/**
	 * @param string $type
	 * @return array<string>
	 */
	public function findTreePropertiesNamesByType($type)
	{
		$componentNames = array();
		foreach ($this->getPropertiesInfos() as $name => $infos)
		{
			if ($infos->isTreeNode() && $infos->isDocument() && $infos->acceptType($type))
			{
				$componentNames[] = $name;
			}
		}

		foreach ($this->getInverseProperties() as $name => $infos)
		{
			if ($infos->isTreeNode() && $infos->isDocument() && $infos->acceptType($type))
			{
				// The most specific is suposed to be the last one.
				// Cf generator_PersistentModel::generatePhpModel().
				$componentNames[$infos->getDbTable() . '.' . $infos->getDbMapping()] = $name;
			}
		}
		return array_values($componentNames);
	}
	
	protected function loadChildrenProperties()
	{
		$this->m_childrenProperties = array();
	}
	
	/**
	 * @return array<ChildPropertyInfo>
	 */
	public final function getChildrenPropertiesInfos()
	{
		if ($this->m_childrenProperties === null) {$this->loadChildrenProperties();}
		return $this->m_childrenProperties;
	}


	/**
	 * @param string $propertyName
	 * @return ChildPropertyInfo
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
	 * @param string $modelName
	 * @return boolean
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

	public final function hasCascadeDelete()
	{
		foreach ($this->getPropertiesInfos() as $name => $info)
		{
			if ($info->isCascadeDelete())
			{
				return true;
			}
		}
		return false;
	}

	protected function loadInvertProperties()
	{
		$this->m_invertProperties = array();
	}

	/**
	 * @return array<PropertyInfo>
	 */
	public final function getInverseProperties()
	{
		if ($this->m_invertProperties === null) {$this->loadInvertProperties();}
		return $this->m_invertProperties;
	}
	
	/**
	 * @param String $name
	 * @return Boolean
	 */
	public final function hasInverseProperty($name)
	{
		if ($this->m_invertProperties === null) {$this->loadInvertProperties();}
		return isset($this->m_invertProperties[$name]);
	}

	/**
	 * @param String $name
	 * @return PropertyInfo
	 */
	public final function getInverseProperty($name)
	{
		if ($this->m_invertProperties === null) {$this->loadInvertProperties();}
		if (isset($this->m_invertProperties[$name]))
		{
			return $this->m_invertProperties[$name];
		}
		return null;
	}

	/**
	 * @return array<String>
	 */
	public final function getPreservedPropertiesNames()
	{
		return $this->m_preservedPropertiesNames;
	}

	/**
	 * @param String $name
	 * @return Boolean
	 */
	public final function isPreservedProperty($name)
	{
		return isset($this->m_preservedPropertiesNames[$name]);
	}



	/**
	 * @see f_mvc_BeanModel::getBeanName()
	 *
	 * @return String
	 */
	function getBeanName()
	{
		return $this->getDocumentName();
	}

	/**
	 * @see f_mvc_BeanModel::getBeanPropertiesInfos()
	 *
	 * @return array<String,
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
	 * @see f_mvc_BeanModel::getBeanPropertyInfo()
	 *
	 * @param string $propertyName
	 * @return BeanPropertyInfo
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
	 * @see f_mvc_BeanModel::hasBeanProperty()
	 *
	 * @param String $propertyName
	 * @return Boolean
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
	 * (non-PHPdoc)
	 * @see f_mvc/bean/f_mvc_BeanModel#getBeanConstraints()
	 */
	public function getBeanConstraints()
	{
		// empty. TODO: fill it during documents compilation process
	}
	
	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	function hasProperty($propertyName)
	{
		return $this->isProperty($propertyName);
	}

	/**
	 * Return if the document has 2 special properties (correctionid, correctionofid)
	 * @return Boolean
	 */
	abstract public function useCorrection();

	/**
	 * @return Boolean
	 */
	abstract public function hasWorkflow();

	/**
	 * @return String
	 */
	abstract public function getWorkflowStartTask();

	/**
	 * @return array<String, String>
	 */
	abstract public function getWorkflowParameters();

	/**
	 * @return Boolean
	 */
	abstract public function usePublicationDates();
	
	/**
	 * @return f_persistentdocument_DocumentService
	 */
	abstract public function getDocumentService();
	
	/**
	 * @return String
	 */
	public function __toString()
	{
		return $this->getName();
	}

	/**
	 * @param string $name
	 * @param array $arguments
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
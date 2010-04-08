<?php
/**
 * @package framework.persistentdocument
 * f_persistentdocument_PersistentDocumentModel
 */
abstract class f_persistentdocument_PersistentDocumentModel implements f_mvc_BeanModel 
{
	/**
	 * @var array<BeanPropertyInfo>
	 */
	private $beanPropertiesInfo;
	
	private static $m_documentModels;

	/**
	 * @var array<PropertyInfo>
	 */
	protected  $m_properties;
	protected  $m_serialisedproperties;
	
	protected  $m_propertiesNames;
	protected  $m_preservedPropertiesNames;

	protected  $m_statuses;
	protected  $m_formProperties;
	protected  $m_childrenProperties;
	protected  $m_invertProperties;
	/**
	 * @var String[]
	 */
	protected  $m_childrenNames;
	
	/**
	 * @var String
	 */
	protected  $m_parentName;

	/**
	 * @var f_persistentdocument_PersistentDocumentModel
	 */
	protected  $m_sourceInjectionModel = null;

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
	 * Get instance from complet document model name
	 * @param string $documentModelName Ex : modules_<generic>/<folder>
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	public static function getInstanceFromDocumentModelName($documentModelName)
	{
		$res  = Framework::parseComponentType($documentModelName);
		if ($res['package_type']!="modules")
		{
			throw new FrameworkException("type_must_be_a_module");
		}

		return  self::getInstance($res['package_name'], $res['component']);
	}
	
	/**
	 * @param String $documentModelName
	 * @return String the corresponding document class name
	 * @example documentModelNameToDocumentClassName("modules_mymodule/mydocument") returns mymodule_persistentdocument_mydocument
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
			throw new FrameworkException("module-name-cannot-be-empty");
		}
		if (empty($documentName))
		{
			throw new FrameworkException("module-type-cannot-be-empty");
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
	 * Enter description here...
	 *
	 * @param String $moduleName
	 * @param String $documentName
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	static function getNewModelInstance($moduleName, $documentName)
	{
		$className = self::getClassNameFromDocument($moduleName, $documentName);
		if ( ! f_util_ClassUtils::classExistsNoLoad($className) )
		{
			// TODO see ServiceLoader->load()
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
		$res  = Framework::parseComponentType($documentModelName);
		if ($res['package_type']!="modules")
		{
			throw new FrameworkException("type_must_be_a_module");
		}

		return f_util_ClassUtils::classExists(self::getClassNameFromDocument($res['package_name'], $res['component']));
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
	 * @return unknown
	 */
	public static function getDocumentModelNamesByModules()
	{
		return unserialize(file_get_contents(f_util_FileUtils::buildChangeBuildPath('documentmodels.php')));
	}

	protected function __construct($documentModelname)
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
	 * @return String
	 */
	abstract public function getLabel();

	/**
	 * @return String
	 */
	abstract public function getName();

	/**
	 * @return String
	 */
	abstract public function getBaseName();

	/**
	 * @return String
	 */
	abstract public function getModuleName();

	/**
	 * @return String
	 */
	abstract public function getDocumentName();

	/**
	 * @return String
	 */
	abstract public function getTableName();

	/**
	 * @return Boolean
	 */
	abstract public function isInternationalized();

	/**
	 * @return Boolean
	 */
	abstract public function isLocalized();
	
	/**
	 * @return String[]
	 */
	public function getChildrenNames()
	{
		return $this->m_childrenNames;
	}
	
	/**
	 * @return Boolean
	 */
	function hasChildren()
	{
		return $this->m_childrenNames !== null;
	}
	
	/**
	 * @return String
	 */
	function getParentName()
	{
		return $this->m_parentName;
	}
	
	function getDocumentClassName()
	{
		return $this->getModuleName()."_persistentdocument_".$this->getDocumentName();
	}
	
	/**
	 * @return Boolean
	 */
	function hasParent()
	{
		return $this->m_parentName !== null;
	}

	/**
	 * @return Boolean
	 */
	abstract public function isLinkedToRootFolder();

	/**
	 * @return Boolean
	 */
	abstract public function isIndexable();
	
	/**
	 * @return Boolean
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
	 * @param String $modelName
	 * @return Boolean
	 */
	public final function isModelCompatible($modelName)
	{
		switch ($modelName)
		{
			case '*':
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
	 * @example Convert model name from 'modules_generic/folder' to 'modules_generic_folder'
	 * @return string
	 */
	public final function getBackofficeName()
	{
		return self::convertModelNameToBackoffice($this->getName());
	}

	/**
	 * @return array<String>
	 */
	public final function getStatuses()
	{
		return array_keys($this->m_statuses);
	}

	/**
	 * @param String $status
	 * @return Boolean
	 */
	public final function hasSatutsCode($status)
	{
		return array_search($status, $this->m_statuses) !== false;
	}

	/**
	 * @return String
	 */
	abstract public function getDefaultNewInstanceStatus();


	/**********************************************************/
	/* CSV Import configuration                               */
	/**********************************************************/
	abstract public function getSynchronize();

	public function getSynchronizedComponents()
	{
		$results=array();
		foreach ($this->getSynchronize() as $componentName => $sourceinfo)
		{
			try
			{
				foreach ($sourceinfo as $sourceName => $datasource)
				{
					foreach ($datasource  as $datasourcesTag)
					{
						$results[$sourceName][$datasourcesTag["ressource"]][$componentName][]= $datasourcesTag;
					}

				}

			}
			catch(ClassException $e)
			{
				$e->shutdown();
				$this->statuses=array();
				//no workflow
			}

		}
		return $results;
	}

	public static function getSynchronizationSources()
	{
		$results=array();
		foreach (self::getDocumentModels() as $type => $documentModel)
		{
			$sources = $documentModel->getSynchronizedComponents();
			$results[$type] = $sources;
		}
		return $results;
	}

	/**********************************************************/
	/* Properties Informations                                 */
	/**********************************************************/

	/**
	 * @return array<String, PropertyInfo> ie. <propName, propertyInfo> 
	 */
	public final function getPropertiesInfos()
	{
		return $this->m_properties;
	}
	
	/**
	 * @var array
	 */
	private static $systemProperties;
	
	public final function getVisiblePropertiesInfos()
	{
		if (self::$systemProperties === null)
		{
			self::$systemProperties = array('id' => true, 
				'model' => true, 
				'author' => true, 
				'authorid' => true, 
				'creationdate' => true, 
				'modificationdate' => true, 
				'publicationstatus' => true, 
				'lang' => true,
				'metastring' => true,  
				'modelversion' => true, 
				'documentversion' => true);
		}
		$properties = array();
		foreach ($this->getEditablePropertiesInfos() as $propName => $propInfo)
		{
			if (!isset(self::$systemProperties[$propName]))
			{
				$properties[$propName] = $propInfo;
			}
		}
		return $properties;
	}

	/**
	 * @param string $propertyName
	 * @return PropertyInfo
	 */
	public final function getProperty($propertyName)
	{
		if (isset($this->m_properties[$propertyName]))
		{
			return $this->m_properties[$propertyName];
		}
		return null;
	}
	
	/**
	 * @return array<String, PropertyInfo> ie. <propName, propertyInfo> 
	 */
	public final function getSerializedPropertiesInfos()
	{
		return $this->m_serialisedproperties;
	}
	
	/**
	 * @param string $propertyName
	 * @return PropertyInfo
	 */	
	public final function getSerializedProperty($propertyName)
	{
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
		return array_merge($this->m_properties, $this->m_serialisedproperties);
	}	
		
	/**
	 * @param string $propertyName
	 * @return PropertyInfo
	 */	
	public final function getEditableProperty($propertyName)
	{
		if (isset($this->m_properties[$propertyName]))
		{
			return $this->m_properties[$propertyName];
		} 
		else if (isset($this->m_serialisedproperties[$propertyName]))
		{
			return $this->m_serialisedproperties[$propertyName];
		}
		return null;
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
		if (is_null($this->m_propertiesNames))
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

	/**
	 * @return array<FormPropertyInfo>
	 */
	public final function getFormPropertiesInfos()
	{
		return $this->m_formProperties;
	}


	/**
	 * @param string $propertyName
	 * @return FormPropertyInfo
	 */
	public final function getFormProperty($propertyName)
	{
		if (array_key_exists($propertyName, $this->m_formProperties))
		{
			return $this->m_formProperties[$propertyName];
		}
		return null;
	}

	/**
	 * @return array<ChildPropertyInfo>
	 */
	public final function getChildrenPropertiesInfos()
	{
		return $this->m_childrenProperties;
	}


	/**
	 * @param string $propertyName
	 * @return ChildPropertyInfo
	 */
	public final function getChildProperty($propertyName)
	{
		if (array_key_exists($propertyName, $this->m_childrenProperties))
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
	 * @return array<String> default array(0 => 'id')
	 */
	public final function getPrimaryKey()
	{
		$keys = array();
		foreach ($this->getPropertiesInfos() as $name => $info)
		{
			if ($info->isPrimaryKey())
			{
				$keys[] = $name;
			}
		}
		return $keys;
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

	/**
	 * @return Boolean
	 */
	public final function isDocumentIdPrimaryKey()
	{
		return $this->m_properties['id']->isPrimaryKey();
	}

	/**
	 * @param String $name
	 * @return Boolean
	 */
	public final function hasInverseProperty($name)
	{
		return array_key_exists($name, $this->m_invertProperties);
	}

	/**
	 * @param String $name
	 * @return PropertyInfo
	 */
	public final function getInverseProperty($name)
	{
		return $this->m_invertProperties[$name];
	}

	/**
	 * @return array<PropertyInfo>
	 */
	public final function getInverseProperties()
	{
		return $this->m_invertProperties;
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
	 * @return Boolean
	 */
	public function isInjectedModel()
	{
		return ($this->m_sourceInjectionModel !== null);
	}

	/**
	 * @deprecated with no replacement
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	public function getInjectionModel()
	{
		return  ($this->m_sourceInjectionModel === null) ? null : $this;
	}


	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	public function getSourceInjectionModel()
	{
		return $this->m_sourceInjectionModel;
	}

	/**
	 * Returns the name of the original model that has been injected.
	 * @return String
	 */
	public function getOriginalModelName()
	{
		return $this->isInjectedModel() ? $this->getSourceInjectionModel()->getName() : $this->getName();
	}
	
	/**
	 * Returns the name of the original module that has been injected.
	 * @return String
	 */
	public function getOriginalModuleName()
	{
		return $this->isInjectedModel() ? $this->getSourceInjectionModel()->getModuleName() : $this->getModuleName();
	}
	
	/**
	 * Returns the name of the original document that has been injected.
	 * @return String
	 */
	public function getOriginalDocumentName()
	{
		return $this->isInjectedModel() ? $this->getSourceInjectionModel()->getDocumentName() : $this->getDocumentName();
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
			$this->beanPropertiesInfo[$propertyName] = new f_persistentdocument_PersistentDocumentBeanPropertyInfo($this->getModuleName(), $this->getDocumentName(), $propertyInfo, $this->getFormProperty($propertyName));
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
	abstract public function publishOnDayChange();
	
	/**
	 * @return f_persistentdocument_DocumentService
	 */
	abstract public function getDocumentService();
	
	/**
	 * @return String or null
	 */
	abstract public function getEditModule();
}
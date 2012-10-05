<?php
use Change\Documents\DocumentHelper;

/**
 * @package framework.persistentdocument
 * f_persistentdocument_PersistentDocumentModel
 */
abstract class f_persistentdocument_PersistentDocumentModel extends \Change\Documents\AbstractModel implements f_mvc_BeanModel 
{
	/**
	 * @deprecated
	 */
	const PRIMARY_KEY_ID = "id";
	
	/**
	 * @var \Change\Documents\Property[]
	 */
	protected  $m_childrenProperties;
	
	/**
	 * @deprecated
	 */
	public static function buildDocumentModelName($moduleName, $documentName)
	{
		return \Change\Documents\ModelManager::getInstance()->composeModelName($moduleName, $documentName);
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
	 * @deprecated
	 */
	public static function getInstanceFromDocumentModelName($modelName)
	{
		$model = \Change\Documents\ModelManager::getInstance()->getModelByName($modelName);
		if ($model === null)
		{
			throw new BaseException("type_must_be_a_module");
		}
		return  $model;
	}
	
	/**
	 * @deprecated
	 */
	public static function documentModelNameToDocumentClassName($documentModelName)
	{
		return self::getInstanceFromDocumentModelName($documentModelName)->getDocumentClassName();
	}

	/**
	 * @deprecated
	 */
	public static function getInstance($moduleName, $documentName)
	{
		$mm = \Change\Documents\ModelManager::getInstance();
		$documentModelName = $mm->composeModelName($moduleName, $documentName);
		
		$model = $mm->getModelByName($documentModelName);
		if ($model === null)
		{
			if ($moduleName != 'generic' && $documentName == 'folder')
			{
				$model = $mm->getModelByName($mm->composeModelName('generic', $documentName));
			}
			else
			{
				throw new Exception('Unknown document model ' . $documentModelName);
			}
		}
		return $model;
	}

	/**
	 * @deprecated
	 */
	static function getNewModelInstance($moduleName, $documentName)
	{
		return self::getInstance($moduleName, $documentName);
	}

	/**
	 * @deprecated
	 */
	public static function exists($documentModelName)
	{
		$model = \Change\Documents\ModelManager::getInstance()->getModelByName($documentModelName);
		return $model != null;
	}

	/**
	 * @deprecated
	 */
	public static function getDocumentModels()
	{
		return \Change\Documents\ModelManager::getInstance()->getModels();
	}
	
	/**
	 * @deprecated
	 */
	public static function getDocumentModelNamesByModules()
	{
		return \Change\Documents\ModelManager::getInstance()->getModelNamesByModules();
	}
	
	/**
	 * @deprecated
	 */
	public static function getModelChildrenNames($modelName = null)
	{
		return \Change\Documents\ModelManager::getInstance()->getChildrenModelNames();
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
		return \Change\Documents\ModelManager::getInstance()->getPublicationStatuses();
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
		return \Change\Documents\DocumentHelper::getSystemPropertyNames();
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
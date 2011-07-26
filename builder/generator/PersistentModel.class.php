<?php
/**
 * @package framework.builder.generator
 */
class generator_PersistentModel
{
	const BASE_MODEL = 'modules_generic/Document';
	const BASE_CLASS_NAME = 'f_persistentdocument_PersistentDocumentImpl';

	private static $m_models;
	private static $m_inverseProperties = array();

	private $name;
	private $moduleName;
	private $documentName;
	/**
	 * extended model name
	 * @var String
	 */
	private $extend;
	/**
	 * @var boolean
	 */
	private $inject;
	
	/**
	 * @var boolean
	 */
	private $injected;

	/**
	 * children models
	 * @var generator_PersistentModel[]
	 */
	private $children = array();

	private $icon;
	private $linkedToRootModule;
	private $hasUrl;
	private $useRewriteUrl;
	private $indexable;
	private $backofficeIndexable;
	private $modelVersion;

	private $tableName;
	private $tableNameOci;

	private $className;
	private $useCorrection;

	private $defaultStatus;
	private $localized;

	private $publishOnDayChange;

	/**
	 * @var generator_Workflow
	 */
	private $workflow;

	private $properties = array();
	private $serializedproperties = array();
	private $formProperties = array();
	private $childrenProperties = array();
	private $inverseProperties = array();
	private $statuses = array();

	private $initSerializedproperties;



	/**
	 * @param String $moduleName
	 * @param String $documentName
	 * @return String
	 */
	public static function buildModelName($moduleName, $documentName)
	{
		return 'modules_' . $moduleName .'/' . $documentName;
	}

	/**
	 * TODO: remove when modulebuilder_ModuleService optimized
	 * @return array<generator_PersistentModel>
	 */
	public static function reloadModels()
	{
		self::$m_models = null;
		return self::loadModels();
	}

	/**
	 * @return generator_PersistentModel[]
	 */
	public static function loadModels($models = null)
	{
		$hasModelParam = null;
		if ($models === null)
		{
			if (self::$m_models !== null)
			{
				return self::$m_models;
			}
			$hasModelParam = false;
			self::$m_models = array();
			$models = &self::$m_models;
		}
		else
		{
			$oldModels = self::$m_models;
			self::$m_models = &$models;
			$hasModelParam = true;
		}
		foreach (array_keys(ModuleService::getInstance()->getPackageVersionList()) as $packageName)
		{
			$dirs = FileResolver::getInstance()->setPackageName($packageName)->getPaths("persistentdocument");
			if (is_null($dirs))
			{
				//self::addMessage("No persistent document for package : $packageName");
				continue;
			}
			foreach ($dirs as $dir)
			{
				$dh = opendir($dir);
				while (($fileName = readdir($dh)) !== false)
				{
					$pathinfo = pathinfo($fileName);
					if (isset($pathinfo['extension']))
					{
						$extension = $pathinfo['extension'];
						$filePath = $dir.DIRECTORY_SEPARATOR.$fileName;
						if (is_file($filePath) && $extension === "xml")
						{
							try
							{
								$module = explode("_",$packageName);
								if ($module[0] != "modules") continue;

								$moduleName = $module[1];
								$documentName = basename($pathinfo['basename'], ".xml");
								if (!isset($models[$packageName.'/'.$documentName]))
								{
									$xmlDoc = self::loadFile($filePath);
									if ($xmlDoc !== null)
									{
										$document = new generator_PersistentModel($xmlDoc, $moduleName, $documentName);
										$models[$document->getName()] = $document;
									}
								}
							}
							catch (BaseException $e)
							{
								if ($e->getMessage() != "document-type-does-not-exists")
								{
									closedir($dh);
									throw $e;
								}
							}
						}
					}
				}
				closedir($dh);
			}
		}

		list($modelResult, $inverseProperties) = self::postImportProcess($models);
		if (!$hasModelParam)
		{
			self::$m_inverseProperties = $inverseProperties;
			self::$m_models = $modelResult;
		}
		else
		{
			$models = $modelResult;
			self::$m_models = $oldModels;
		}

		return $modelResult;
	}

	public static function buildModelsByModuleNameCache()
	{
		$modelsByModule = array();
		foreach (self::loadModels() as $model)
		{
			$moduleName = $model->getModuleName();
			if (!isset($modelsByModule[$moduleName]))
			{
				$modelsByModule[$moduleName] = array($model->getName());
			}
			else 
			{
				$modelsByModule[$moduleName][] = $model->getName();
			}
		}
		
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('documentmodels.php');
		f_util_FileUtils::writeAndCreateContainer($compiledFilePath, serialize($modelsByModule), f_util_FileUtils::OVERRIDE);
	}
	
	public static function buildModelsChildrenCache()
	{
		$modelsChildren = array();
		foreach (self::loadModels() as $model)
		{
			$childrenNames = array();
			foreach ($model->getFinalChildren() as $child)
			{
				$childrenNames[] = $child->getName();
			}
			if (count($childrenNames) > 0)
			{
				$modelsChildren[$model->getName()] = $childrenNames;
			}
		}
		
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('documentmodelschildren.php');
		f_util_FileUtils::writeAndCreateContainer($compiledFilePath, serialize($modelsChildren), f_util_FileUtils::OVERRIDE);
	}
	
	public static function buildPublishListenerInfos()
	{
		$publishListenerInfos = array();
		$rc = RequestContext::getInstance();
		foreach (self::loadModels() as $model)
		{	
			if (!$model->hasPublishOnDayChange()) {continue;}
			
			$modelName = $model->getName();
			while ($model)
			{
				$pubproperty = $model->getPropertyByName('publicationstatus');
				if ($pubproperty !== null) {break;}
				$model = $model->getParentModelOrInjected();
			}
			
			if ($pubproperty)
			{
				if ($pubproperty->isLocalized())
				{
					$langs = $rc->getSupportedLanguages();
				}
				else
				{
					$langs = array($rc->getDefaultLang());
				}
				$publishListenerInfos[$modelName] = $langs;
			}
		}	
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('publishListenerInfos.ser');
		f_util_FileUtils::writeAndCreateContainer($compiledFilePath, serialize($publishListenerInfos), f_util_FileUtils::OVERRIDE);
	}
	
	public static function buildDocumentPropertyInfos()
	{
		$documentPropertyInfos = array();
		foreach (self::loadModels() as $model)
		{	
			foreach ($model->getProperties() as $property)
			{
				if ($property->isDocument())
				{
					$documentPropertyInfos[$property->getName()] = true;
				}
			}
		}
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('relationNameInfos.ser');
		f_util_FileUtils::writeAndCreateContainer($compiledFilePath, serialize(array_keys($documentPropertyInfos)), f_util_FileUtils::OVERRIDE);		
	}
	
	public static function buildIndexableDocumentInfos()
	{
		$indexableDocumentInfos = array('fo' => array(), 'bo' => array());
		foreach (self::loadModels() as $model)
		{	
			if ($model instanceof generator_PersistentModel)
			{
				if ($model->injected()) {continue;}
				
				if ($model->inject())
				{
					$moduleName = strtoupper($model->getParentModel()->getModuleName());
					$documentName = strtoupper($model->getParentModel()->getDocumentName());
					$modelName = $model->getParentModelName();
				}
				else
				{
					$moduleName = strtoupper($model->getModuleName());
					$documentName = strtoupper($model->getDocumentName());
					$modelName = $model->getName();
				}
				
				if ($model->hasURL() &&  $model->isIndexable() 
						&& !defined('MOD_'. $moduleName .'_'.$documentName .'_DISABLE_INDEXATION'))
				{
					$indexableDocumentInfos['fo'][] = $modelName;
				}
				
				if ($model->isBackofficeIndexable() && 
					(!defined('MOD_'. $moduleName .'_'.$documentName .'_DISABLE_BACKOFFICE_INDEXATION') 
						|| !constant('MOD_'. $moduleName .'_'.$documentName .'_DISABLE_BACKOFFICE_INDEXATION')))
				{
					$indexableDocumentInfos['bo'][] = $modelName;
				}
			}
		}
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('indexableDocumentInfos.ser');
		f_util_FileUtils::writeAndCreateContainer($compiledFilePath, serialize($indexableDocumentInfos), f_util_FileUtils::OVERRIDE);		
	}
	
	public static function buildAllowedDocumentInfos()
	{
		$allowedDocumentInfos = array ('hasUrl' => array(), 'useRewriteUrl' => array());
		foreach (self::loadModels() as $model)
		{	
			if ($model instanceof generator_PersistentModel)
			{
				if ($model->injected()) {continue;}
				
				if ($model->hasURL())
				{
					$modelName = ($model->inject()) ? $model->getParentModelName() : $model->getName();
					$allowedDocumentInfos['hasUrl'][] = $modelName;
					if ($model->useRewriteURL())
					{
						$allowedDocumentInfos['useRewriteUrl'][] = $modelName;
					}
				}
			}
		}
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('allowedDocumentInfos.ser');
		f_util_FileUtils::writeAndCreateContainer($compiledFilePath, serialize($allowedDocumentInfos), f_util_FileUtils::OVERRIDE);	
	}
	
	/**
	 * @param String $xml
	 * @param String $module
	 * @param String $name
	 * @return generator_PersistentModel
	 */
	static function loadModelFromString($xml, $moduleName, $documentName)
	{
		$models = array();
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($xml);
		$model = new generator_PersistentModel($xmlDoc->documentElement, $moduleName, $documentName);
		$models = array($model->getName() => $model);
		$models = self::loadModels($models);
		return $models[$model->getName()];
	}

	/**
	 * @return array $models, $inverseProperties
	 */
	private static function postImportProcess($models)
	{
		$inversePropertiesByModel = array();
		$virtualModel = $models[self::BASE_MODEL];
		unset($models[self::BASE_MODEL]);

		// Set common properties.
		foreach ($models as $model)
		{
			
			if (is_null($model->extend) || $model->extend == self::BASE_MODEL)
			{
				$model->applyGenericDocumentModel($virtualModel);
			}
			else
			{
				if (!isset($models[$model->extend]))
				{
					throw new Exception("Could not find extended model ".$model->extend);
				}
				
				if ($model->isLocalized() && !$models[$model->extend]->isLocalized())
				{
					throw new Exception("Can not render model ".$model->name." localized while ".$models[$model->extend]->name." is not");
				}
				
				if ($model->useCorrection && !$models[$model->extend]->useCorrection)
				{
					throw new Exception("Can not activate correction on ".$model->name." while not activated on ".$models[$model->extend]->name);
				}
				
				if ($model->inject)
				{
					$models[$model->extend]->injected = true;
					$models[$model->extend]->replacer = $model;
				}
				
				$model->generateS18sPropertyIfNeeded();
				
				$extendedModel = $models[$model->extend];
				while ($extendedModel !== null && $extendedModel->getName() != self::BASE_MODEL)
				{
					$extendedModel->children[] = $model;
					if ($extendedModel->extend === null || $extendedModel->extend === self::BASE_MODEL)
					{
						break;
					}
					$extendedModel = $models[$extendedModel->extend];
				}
			}
		}

		// Check heritage.
		foreach ($models as $model)
		{
			$model->checkOverrideProperties();
		}

		// Check constraints and inverse property.
		foreach ($models as $model)
		{
			foreach ($model->getProperties() as $property)
			{
			
				$property->applyDefaultConstraints();

				if ($property->isInverse() && $property->isDocument())
				{
					$destModel = $property->getType();
					if (!array_key_exists($destModel, $inversePropertiesByModel))
					{
						$inversePropertiesByModel[$destModel] = array();
					}
					$inversePropertiesByModel[$destModel][] = generator_PersistentProperty::generateInverseProperty($property);
				}
			}
		}

		foreach ($inversePropertiesByModel as $modelName => $inverseProperties)
		{
			$model = $models[$modelName];
			if (is_null($model))
			{
				self::addMessage("invalid inverse property on document $modelName");
				die();
			}
			foreach ($inverseProperties as $property)
			{
				$model->inverseProperties[] = $property;
			}
		}
		return array($models, $inversePropertiesByModel);
	}
	
	public function generateS18sPropertyIfNeeded()
	{
		if (count($this->serializedproperties) > 0 && $this->getPropertyByName("s18s") === null)
		{
			$parent = $this->getParentModel();
			$notFounded = true;
			while ($parent !== null && $notFounded)
			{
				 $notFounded = $parent->getPropertyByName("s18s") === null;
				 $parent = $parent->getParentModel();
			}
			if ($notFounded)
			{
				$this->initSerializedproperties = true;
				$localized = false;
				foreach ($this->serializedproperties as $property)
				{
					if ($property->isLocalized())
					{
						$localized = true;
						break;
					}
				}
				if (!$this->localized && $localized)
				{
					$this->setLocalized();
				}
				
				$property = generator_PersistentProperty::generateS18sProperty($this, $localized);
				$this->addProperty($property);
			}
		}
	}

	/**
	 * @param String $modelName
	 * @return generator_PersistentModel
	 */
	public static function getModelByName($modelName)
	{
		self::loadModels();
		return self::$m_models[$modelName];
	}

	/**
	 * @param String $message
	 */
	public static function addMessage($message)
	{
		echo $message . "\n";
	}

	/**
	 * @param Boolean $value
	 * @return String
	 */
	public static function escapeBoolean($value)
	{
		return self::getBoolean($value) ? 'true' : 'false';
	}

	/**
	 * @param String $value
	 * @return String
	 */
	public static function escapeString($value)
	{
		if (is_null($value))
		{
			return 'null';
		}
		return "'" . str_replace("'", "\\'", $value) . "'";
	}

	/**
	 * @param DOMElement $xml
	 * @param String $moduleName
	 * @param String $documentName
	 */
	private function workflowHook($xml, $moduleName, $documentName)
	{
		$path = FileResolver::getInstance()->setPackageName('modules_' . $moduleName)->getPath('config'.DIRECTORY_SEPARATOR.$documentName.'.workflow.xml');
		if ($path !== null && is_readable($path))
		{
			$workflowDoc = new DOMDocument();
			$workflowDoc->load($path);
			$rootelement = $workflowDoc->documentElement;
			if ($rootelement !== null)
			{
				$nodeList = $rootelement->getElementsByTagName('workflow');
				if ($nodeList->length == 1)
				{
					$basenodelist = $xml->getElementsByTagName('workflow');
					if ($basenodelist->length == 1)
					{
						$xml->removeChild($basenodelist->item(0));
					}
					$newworkflownode = $xml->ownerDocument->importNode($nodeList->item(0), true);
					$xml->appendChild($newworkflownode);
				}

				$nodeList = $rootelement->getElementsByTagName('statuses');
				if ($nodeList->length == 1)
				{
					$basenodelist = $xml->getElementsByTagName('statuses');
					if ($basenodelist->length == 1)
					{
						$xml->removeChild($basenodelist->item(0));
					}
					$newworkflownode = $xml->ownerDocument->importNode($nodeList->item(0), true);
					$xml->appendChild($newworkflownode);
				}
			}
			else
			{
				self::addMessage("Invalid workflow configuration file : " . $path);
			}
		}
	}


	/**
	 * @param DomElement $filePath
	 * @param String $moduleName
	 * @param String $documentName
	 */
	protected function __construct($xml, $moduleName, $documentName)
	{
		$this->moduleName = $moduleName;
		$this->documentName = $documentName;
		$this->name = self::buildModelName($moduleName, $documentName);
		if ($this->name == self::BASE_MODEL)
		{
			$property = generator_PersistentProperty::generateIdProperty($this);
			$this->addProperty($property);

			$property = generator_PersistentProperty::generateModelProperty($this);
			$this->addProperty($property);
		}

		$this->workflowHook($xml, $moduleName, $documentName);

		$this->importAttributes($xml);

		$this->importProperties($xml);

		$this->importSerializedProperties($xml);

		$this->importFormProperties($xml);

		$this->importChildrenProperties($xml);

		$this->importPublicationStatus($xml);

		$this->importWorkflow($xml);
	}

	/**
	 * @param String $filePath
	 * @return DomElement
	 */
	private static function loadFile($filePath)
	{
		$doc = new DOMDocument();
		$doc->load($filePath);
		$xml = $doc->documentElement;
		if ($xml === null)
		{
			self::addMessage("Invalid document model : " .$filePath);
			return null;
		}
		return $xml;
	}

	/**
	 * @param String $value
	 * @return Boolean
	 */
	public static function getBoolean($value)
	{
		$value = strtolower($value);
		if ($value === true || $value == "true" || $value == "yes" || $value == "1")
		{
			return true;
		}
		return false;
	}
	
	function injected()
	{
		return $this->injected === true;
	}
	
	function inject()
	{
			return $this->inject === true;
	}
	
	/**
	 * @var generator_PersistentModel
	 */
	private $replacer;
	
	/**
	 * @return generator_PersistentModel
	 */
	function getReplacer()
	{
		return $this->replacer;
	}

	/**
	 * @return Boolean
	 */
	public function hasParentModel()
	{
		return (!is_null($this->extend));
	}
	
	/**
	 * @return Boolean
	 */
	public function hasFinalParentModel()
	{
		if ($this->inject())
		{
			return $this->getParentModel()->hasParentModel();
		}
		return $this->hasParentModel();
	}

	/**
	 * @return generator_PersistentModel
	 */
	public function getParentModel()
	{
		if (!is_null($this->extend))
		{
			return self::$m_models[$this->extend];
		}
		return null;
	}
	
	/**
	 * @return Boolean
	 */
	public function isParentModelBackofficeIndexable()
	{
		$parentModel = $this->getParentModel();
		return $parentModel !== null && $parentModel->isBackofficeIndexable();
	}
	
	/**
	 * @return generator_PersistentModel
	 */
	public function getParentModelOrInjected()
	{
		if ($this->inject())
		{
			return $this->getParentModel();
		}
		$parent = $this->getParentModel();
		if ($parent === null)
		{
			return null;
		}
		//if ($parent->injected() && $parent->getReplacer()->name != $this->name)
		if ($parent->injected())
		{
			return $parent->getReplacer();
		}
		return $parent;
	}
	
	/**
	 * @return generator_PersistentModel
	 */
	public function getFinalParentModel()
	{
		if ($this->inject())
		{
			return $this->getParentModel()->getFinalParentModel();
		}
		return $this->getParentModel();
	}

	public function getParentModelName()
	{
		if ($this->extend === null)
		{
			return null;
		}
		return $this->getParentModel()->getName();
	}
	
	public function getFinalParentModelName()
	{
		if (!$this->hasFinalParentModel())
		{
			return null;
		}
			
		return $this->getFinalParentModel()->getName();
	}

	/**
	 * @return String
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return String
	 */
	public function getModuleName()
	{
		return $this->moduleName;
	}

	/**
	 * @return String
	 */
	public function getDocumentName()
	{
		return $this->documentName;
	}

	function getFinalModuleName()
	{
		if ($this->inject)
		{
			$matches = null;
			if (preg_match('/^modules_(.*)\/(.*)$/', $this->extend, $matches))
			{
				return $matches[1];
			}
			throw new Exception("Unable to parse ".$this->extend);
		}
		return $this->moduleName;
	}
	
	function useDocumentEditor()
	{
		$path = f_util_FileUtils::buildWebeditPath('modules', $this->getFinalModuleName(), 'config', 'perspective.xml');
		return file_exists($path);
	}
	
	function getFinalDocumentName()
	{
		if ($this->inject)
		{
			$matches = null;
			if (preg_match('/^modules_(.*)\/(.*)$/', $this->extend, $matches))
			{
				return $matches[2];
			}
			throw new Exception("Unable to parse ".$this->extend);
		}
		return $this->documentName;
	}

	function getFinalName()
	{
		if ($this->inject)
		{
			if (preg_match('/^modules_(.*)\/(.*)$/', $this->extend))
			{
				return $this->extend;
			}
			throw new Exception("Unable to parse ".$this->extend);
		}
		return $this->name;
	}

	function getFinalDocumentClassName()
	{
		if ($this->inject)
		{
			$matches = null;
			if (preg_match('/^modules_(.*)\/(.*)$/', $this->extend, $matches))
			{
				return $matches[1]."_persistentdocument_".$matches[2];
			}
			throw new Exception("Unable to parse ".$this->extend);
		}
		return $this->getDocumentClassName();
	}

	/**
	 * @param String[] $models
	 * @return String[]
	 */
	public function getCompatibleModel($models = null)
	{
		if (is_null($models))
		{
			$models = array("*", self::BASE_MODEL);
		}
		if (!is_null($this->extend))
		{
			$models =  self::getModelByName($this->extend)->getCompatibleModel($models);
		}
		$models[] = $this->getName();

		return $models;
	}

	/**
	 * @param String[] $models
	 * @return String[]
	 */
	public function getAncestorModels($models = null)
	{
		if ($models === null)
		{
			$models = array();
		}
		else 
		{
			$models[] = $this->getName();
		}
		if ($this->extend !== null)
		{
			$models = self::getModelByName($this->extend)->getAncestorModels($models);
		}
		return $models;
	}

	/**
	 * @return String[]
	 */
	function getChildren()
	{
		return $this->children;
	}
	
	/**
	 * @return String[]
	 */
	function getFinalChildren()
	{
		if ($this->inject())
		{
			$children = self::$m_models[$this->extend]->children;
			if ($children !== null)
			{
				$newChildren = array();
				foreach ($children as $key => $child)
				{
					if ($child->name != $this->name && !$child->inject())
					{
						$newChildren[] = $child;
					}
				}
				if (count($newChildren) > 0)
				{
					return $newChildren;
				}
				return null;
			}
		}
		return $this->children;
	}

	/**
	 * @param String $name
	 * @return generator_PersistentProperty
	 */
	function getPropertyByName($name)
	{
		foreach ($this->properties as $property)
		{
			if ($property->getName() == $name)
			{
				return $property;
			}
		}
		return null;
	}

	/**
	 * @param String $name
	 * @return generator_FormProperty
	 */
	private function getFormPropertyByName($name)
	{
		foreach ($this->formProperties as $property)
		{
			if ($property->getName() == $name)
			{
				return $property;
			}
		}
		return null;
	}

	/**
	 * @param String $name
	 * @return generator_ChildrenProperty
	 */
	private function getChildrenPropertyByName($name)
	{
		foreach ($this->childrenProperties as $property)
		{
			if ($property->getName() == $name)
			{
				return $property;
			}
		}
		return null;
	}

	/**
	 * @param String $name
	 * @return generator_PersistentProperty
	 */
	private function getSerializedPropertyByName($name)
	{
		foreach ($this->serializedproperties as $property)
		{
			if ($property->getName() == $name)
			{
				return $property;
			}
		}
		return null;
	}

	/**
	 * @param String $name
	 * @return generator_ChildrenProperty
	 */
	private function getInversePropertyByName($name)
	{
		foreach ($this->inverseProperties as $property)
		{
			if ($property->getName() == $name)
			{
				return $property;
			}
		}
		return null;
	}

	/**
	 * @param generator_PersistentModel $baseDocument
	 */
	private function applyGenericDocumentModel($baseDocument)
	{

		$this->extend = null;
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$properties = array('tableName' => $this->tableName, 'moduleName' => $this->moduleName, 'documentName' => $this->documentName, 'tableNameOci' => $this->tableNameOci);
		$this->tableName = $pp->generateTableName($properties);

		foreach (array_reverse($baseDocument->properties) as $property)
		{
			$propertyName = $property->getName();
			$newProperty = $this->getPropertyByName($propertyName);
			if (is_null($newProperty))
			{
				$newProperty = clone($property);
				$newProperty->setModel($this);
				// Use this to preserve the generic > specific order of the properties
				$props = array_reverse($this->properties, true);
				$props[$newProperty->getId()] = $newProperty;
				$this->properties = array_reverse($props, true);
			}
			else
			{
				$newProperty->mergeGeneric($property);
			}
		
		}

		foreach ($baseDocument->formProperties as $key => $property)
		{
			$propertyName = $property->getName();
			$newProperty = $this->getFormPropertyByName($propertyName);
			if (is_null($newProperty))
			{
				$newProperty = clone($property);
				$newProperty->setModel($this);
				array_unshift($this->formProperties, $newProperty);
			}
			else
			{
				$newProperty->mergeGeneric($property);
			}
		}

		$this->statuses = $baseDocument->statuses;
		if ($this->defaultStatus === null)
		{
			$this->defaultStatus = $baseDocument->defaultStatus;
		}

		if (is_null($this->backofficeIndexable))
		{
			$this->backofficeIndexable = $baseDocument->backofficeIndexable;
		}

		if ($this->hasUrl === null)
		{
			$this->hasUrl = $baseDocument->hasUrl;
		}
		
		if ($this->useRewriteUrl === null)
		{
			$this->useRewriteUrl = $baseDocument->useRewriteUrl;
		}
		
		if ($this->hasWorkflow())
		{
			$this->useCorrection = true;
		}

		if ($this->useCorrection === null)
		{
			$this->useCorrection = $baseDocument->useCorrection;
		}

		if ($this->useCorrection)
		{
			$property = generator_PersistentProperty::generateCorrectionIdProperty($this);
			$this->addProperty($property);

			$property = generator_PersistentProperty::generateCorrectionOfIdProperty($this);
			$this->addProperty($property);
		}

		if ($this->publishOnDayChange === null)
		{
			$this->publishOnDayChange = $baseDocument->publishOnDayChange;
		}

		/**
		 * Check localisation
		 */
		$this->localized = false;
		foreach ($this->properties as $property)
		{
			if ($property->isLocalized() && !$this->localized)
			{
				$this->setLocalized();
				break;
			}
		}
		
		if ($this->localized)
		{
			$this->getPropertyByName("publicationstatus")->setLocalized();
		}

		/**
		 * Serialized Properties
		 */
		if (count($this->serializedproperties) > 0)
		{
			$this->initSerializedproperties = true;
			$localized = false;
			foreach ($this->serializedproperties as $property)
			{
				if ($property->isLocalized())
				{
					$localized = true;
					break;
				}
			}
			if (!$this->localized && $localized)
			{
				$this->setLocalized();
			}
			$property = generator_PersistentProperty::generateS18sProperty($this, $localized);
			$this->addProperty($property);
		}
	}

	private function setLocalized()
	{
		$this->localized = true;
		$this->getPropertyByName('label')->setLocalized();
		if (!is_null($this->getPropertyByName('correctionid')))
		{
			$this->getPropertyByName('correctionid')->setLocalized();
		}
		$publicationStatus = $this->getPropertyByName('publicationstatus');
		if ($publicationStatus !== null)
		{
			$publicationStatus->setLocalized();	
		}
	}

	private function checkOverrideProperties()
	{
		//echo "CheckOverrideProperties ".$this->name."\n";
		$parentModel = $this->getParentModelOrInjected();
		if (is_null($parentModel))
		{
			//echo "END ***\n";
			return;
		}
		
		//echo $parentModel->getName()."\n";

		$parentModel->checkOverrideProperties();

		$this->tableName = $parentModel->tableName;
		$this->localized = $parentModel->localized;

		if ($this->useCorrection)
		{
			$property = generator_PersistentProperty::generateCorrectionIdProperty($this);
			$this->addProperty($property);

			$property = generator_PersistentProperty::generateCorrectionOfIdProperty($this);
			$this->addProperty($property);
		}
		
		foreach ($this->properties as $property)
		{
			$parentProperty = $parentModel->getPropertyByName($property->getName());
			if (!is_null($parentProperty))
			{
				$property->setParentProperty($parentProperty);
			}
		}

		foreach ($this->formProperties as $property)
		{
			$parentProperty = $parentModel->getFormPropertyByName($property->getName());
			if (!is_null($parentProperty))
			{
				$property->setParentProperty($parentProperty);
			}
		}

		foreach ($this->childrenProperties as $property)
		{
			$parentProperty = $parentModel->getChildrenPropertyByName($property->getName());
			if (!is_null($parentProperty))
			{
				$property->setParentProperty($parentProperty);
			}
		}
		if (count($this->serializedproperties))
		{
			foreach ($this->serializedproperties as $property)
			{
				$parentProperty = $parentModel->getSerializedPropertyByName($property->getName());
				if (!is_null($parentProperty))
				{
					$property->setParentProperty($parentProperty);
				}
			}
		}
	}


	/**
	 * @param String $name
	 * @return generator_PersistentProperty
	 */
	public function getBaseProperty($name)
	{
		$property = $this->getPropertyByName($name);
		if (!is_null($property))
		{
			return $property;
		}
		else if ($this->hasParentModel())
		{
			return $this->getParentModel()->getBaseProperty($name);
		}
		return null;
	}


	/**
	 * @return Boolean
	 */
	public function isLocalized()
	{
		return $this->localized === true;
	}

	/**
	 * @return String
	 */
	public function getDocumentClassName()
	{
		return ''. $this->moduleName .'_persistentdocument_'. $this->documentName;
	}

	/**
	 * @return String
	 */
	public function getImportScriptDocumentClassName()
	{
		return ''. $this->moduleName .'_'. ucfirst($this->documentName) . 'ScriptDocumentElement';
	}

	/**
	 * @return Boolean
	 */
	public function hasURL()
	{
		if ($this->hasUrl === null && $this->hasParentModel())
		{
			return $this->getParentModel()->hasURL();
		}
		else
		{
			return $this->hasUrl;
		}
	}
	
	/**
	 * @return Boolean
	 */
	public function useRewriteURL()
	{
		if ($this->useRewriteUrl === null && $this->hasParentModel())
		{
			return $this->getParentModel()->useRewriteURL();
		}
		else
		{
			return $this->useRewriteUrl;
		}
	}	
	
	/**
	 * @return Boolean
	 */
	public function isIndexable()
	{
		if (is_null($this->indexable) && $this->hasParentModel())
		{
			return $this->getParentModel()->isIndexable();
		}
		else
		{
			return $this->indexable;
		}
	}

	/**
	 * @return Boolean
	 */
	public function isBackofficeIndexable()
	{
		if (is_null($this->backofficeIndexable))
		{
			if ($this->hasParentModel())
			{
				return $this->getParentModel()->isBackofficeIndexable();
			}
			return true;
		}
		else
		{
			return $this->backofficeIndexable;
		}
	}


	/**
	 * @return Boolean
	 */
	public function isLinkedToRootModule()
	{
		if (is_null($this->linkedToRootModule) && $this->hasParentModel())
		{
			return $this->getParentModel()->isLinkedToRootModule();
		}
		else
		{
			return $this->linkedToRootModule;
		}
	}

	/**
	 * @return String
	 */
	public function getDefaultStatus()
	{
		if (is_null($this->defaultStatus) && $this->hasParentModel())
		{
			return $this->getParentModel()->getDefaultStatus();
		}
		else
		{
			return $this->defaultStatus;
		}
	}

	/**
	 * @return Boolean
	 */
	public function hasCorrection()
	{
		if (is_null($this->useCorrection) && $this->hasParentModel())
		{
			return $this->getParentModel()->hasCorrection();
		}
		return $this->useCorrection;
	}

	/**
	 * @return Boolean
	 */
	public function hasPublishOnDayChange()
	{
		if (is_null($this->publishOnDayChange) && $this->hasParentModel())
		{
			return $this->getParentModel()->hasPublishOnDayChange();
		}
		else
		{
			return $this->publishOnDayChange;
		}
	}

	/**
	 * @return generator_Workflow
	 */
	private function getWorkflow()
	{
		if (is_null($this->workflow) && $this->hasParentModel())
		{
			return $this->getParentModel()->getWorkflow();
		}
		else
		{
			return $this->workflow;
		}
	}

	/**
	 * @return Boolean
	 */
	public function hasWorkflow()
	{
		$workflow = $this->getWorkflow();
		if (!is_null($workflow))
		{
			if (!is_null($workflow->getStartTask()) && $workflow->getStartTask() != '')
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @return String
	 */
	public function getWorkflowStartTask()
	{
		if ($this->hasWorkflow())
		{
			return $this->getWorkflow()->getStartTask();
		}
		return null;
	}

	/**
	 * @return String
	 */
	public function getSerializedWorkflowParameters()
	{
		if ($this->hasWorkflow())
		{
			return var_export($this->getWorkflow()->getParameters(), true);
		}
		return 'array()';
	}

	/**
	 * @return String
	 */
	public function getComponentClassName()
	{
		if (is_null($this->className) && $this->hasParentModel())
		{
			return $this->getParentModel()->getComponentClassName();
		}
		else
		{
			return $this->className;
		}
	}

	/**
	 * @return String
	 */
	public function getTableName()
	{
		return $this->tableName;
	}

	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @return array<generator_FormProperty>
	 */
	public function getFormProperties()
	{
		return $this->formProperties;
	}

	/**
	 * @return array<generator_ChildrenProperty>
	 */
	public function getChildrenProperties()
	{
		return  $this->childrenProperties;
	}

	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getInverseProperties()
	{
		return $this->inverseProperties;
	}


	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getSerializedProperties()
	{
		return $this->serializedproperties;
	}

	public function getPreservedPropertiesNames()
	{
		$result = array();
		foreach ($this->getPropertiesComplete() as $property)
		{
			if ($property->getPreserveOldValue())
			{
				$result[] = $property->getName();
			}
		}
		return $result;
	}

	public function hasPreservedProperties()
	{
		return count($this->getPreservedPropertiesNames() != 0);
	}

	public function getStatuses()
	{
		return count($this->statuses) == 0 ? $this->getParentModel()->getStatuses() : $this->statuses;
	}

	/**
	 * @return String
	 */
	public function getBaseClassName()
	{
		if ($this->hasParentModel())
		{
			return $this->getParentModel()->getDocumentClassName();
		}
		return self::BASE_CLASS_NAME;
	}

	/**
	 * @return String
	 */
	public function getBaseServiceClassName()
	{
		if ($this->hasParentModel())
		{
			$parentModel = $this->getParentModel();
			return $parentModel->getModuleName() . '_'. ucfirst($parentModel->getDocumentName()) .'Service';
		}
		return 'f_persistentdocument_DocumentService';
	}

	/**
	 * @return String
	 */
	public function getServiceClassName()
	{
		return $this->getModuleName() . '_'. ucfirst($this->getDocumentName()) .'Service';
	}
	
	/**
	 * @return String
	 */
	public function getFinalServiceClassName()
	{
		return $this->getFinalModuleName() . '_'. ucfirst($this->getFinalDocumentName()) .'Service';
	}

	/**
	 * @return String
	 */
	public function getIcon()
	{
		if (is_null($this->icon) && $this->hasParentModel())
		{
			return $this->getParentModel()->getIcon();
		}
		else
		{
			return $this->icon;
		}
	}

	/**
	 * @return String
	 */
	public function getBaseName()
	{
		if ($this->hasParentModel())
		{
			return $this->getParentModel()->getName();
		}
		return null;
	}



	/**
	 * @return String
	 */
	public function generatePhpModel()
	{
		$generator = new builder_Generator('models');
		$model = clone($this);

		$model->properties = array();
		$model->formProperties = array();
		$model->childrenProperties = array();

		$modelsList = array();
		$currentModel = $this;

		while (!is_null($currentModel))
		{
			array_unshift($modelsList, $currentModel);
			$currentModel = $currentModel->getParentModelOrInjected(); 
		}

		foreach ($modelsList as $currentModel)
		{
			foreach ($currentModel->getProperties() as $property)
			{
				$newproperty = $model->getPropertyByName($property->getName());
				if (is_null($newproperty))
				{
					$newproperty = clone($property);
					$newproperty->setModel($model);
					$model->properties[] = $newproperty;
				}
				else
				{
					$newproperty->override($property);
				}
			}

			foreach ($currentModel->getFormProperties() as $property)
			{
				$newproperty = $model->getFormPropertyByName($property->getName());
				if (is_null($newproperty))
				{
					$newproperty = clone($property);
					$model->formProperties[] = $newproperty;
				}
				else
				{
					$newproperty->override($property);
				}
			}

			foreach ($currentModel->getChildrenProperties() as $property)
			{
				$newproperty = $model->getChildrenPropertyByName($property->getName());
				if (is_null($newproperty))
				{
					$newproperty = clone($property);
					$model->childrenProperties[] = $newproperty;
				}
				else
				{
					$newproperty->override($property);
				}
			}

			foreach ($currentModel->getInverseProperties() as $property)
			{
				$newproperty = $model->getInversePropertyByName($property->getName());
				if (is_null($newproperty))
				{
					$newproperty = clone($property);
					$model->inverseProperties[] = $newproperty;
				}
				else
				{
					$newproperty->override($property);
				}
			}
		}

		// Sort inverse properties in order to always have a parent model before its descendants.
		usort($model->inverseProperties, array('generator_PersistentModel', 'compareInverseProperties'));

		foreach ($model->getProperties() as $property)
		{
			$name = $property->getName();
			if ($name == 'id' || $name == 'model') {continue;}
			$newproperty = $model->getFormPropertyByName($property->getName());
			if (is_null($newproperty))
			{
				$newproperty = new generator_FormProperty($this);
				$model->formProperties[] = $newproperty;
			}
			$newproperty->linkTo($property);
		}

		$generator->assign_by_ref('model', $model);
		$result = $generator->fetch('DocumentModel.tpl');
		return $result;
	}

	/**
	 * @param generator_PersistentProperty $property1
	 * @param generator_PersistentProperty $property2
	 */
	private static function compareInverseProperties($property1, $property2)
	{
		$model1 = $property1->getTypeModel();
		$model2 = $property2->getTypeModel();

		// If one of the models is null, there is an error: an inverse property
		// must have document type.
		if ($model1 === null || $model2 === null)
		{
			throw new Exception('An inverse property must have document type!');
		}

		// If model1 extends model2, property1 > property2.
		$tempModel = $model1;
		while ($tempModel->hasParentModel())
		{
			$tempModel = $tempModel->getParentModel();
			if ($tempModel->getName() == $model2->getName())
			{
				return 1;
			}
		}

		// If model2 extends model1, property1 < property2.
		$tempModel = $model1;
		while ($tempModel->hasParentModel())
		{
			$tempModel = $tempModel->getParentModel();
			if ($tempModel->getName() == $model2->getName())
			{
				return -1;
			}
		}

		// If there is no ineritance relation, the two properties are 'equals'.
		return 0;
	}

	/**
	 * @return String
	 */
	public function generatePhpBaseClass()
	{
		$generator = new builder_Generator('models');
		$generator->assign_by_ref('model', $this);
		$result = $generator->fetch('DocumentClassBase.tpl');
		return $result;
	}

	/**
	 * @return String
	 */
	public function generateImportClass()
	{
		$generator = new builder_Generator('models');
		$generator->assign_by_ref('model', $this);
		$result = $generator->fetch('ImportDocumentClass.tpl');
		return $result;
	}

	/**
	 * @return String
	 */
	public function generatePhpI18nClass()
	{
		$generator = new builder_Generator('models');
		$generator->assign_by_ref('model', $this);
		$result = $generator->fetch('DocumentI18nClass.tpl');
		return $result;
	}


	/**
	 * @return String
	 */
	public function generatePhpOverride()
	{
		$generator = new builder_Generator('models');
		$generator->assign_by_ref('model', $this);
		$result = $generator->fetch('DocumentClass.tpl');
		return $result;
	}

	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getClassMember()
	{
		$result = array();
		foreach ($this->getPropertiesComplete() as $property)
		{
			if (array_search($property->getName(), array('id', 'model', 'lang', 'label')) !== false || $property->isOverride())
			{
				continue;
			}
				
			$result[] = $property;
		}
		return $result;
	}
	
	public function getPropertiesComplete()
	{
		$result = array();
		foreach ($this->properties as $property)		
		{
			$result[] = $property;
		}
		if ($this->injected)
		{
			foreach ($this->replacer->getProperties() as $property)
			{
				if (!$property->isOverride())
				{
					$result[] = $property;
				}
			}
		}
		return $result;
	}

	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getPrivateClassMember()
	{
		$resultat = array();
		foreach ($this->getClassMember() as $property)
		{
			if ($property->isLocalized())
			{
				continue;
			}

			$resultat[] = $property;
		}

		return $resultat;
	}

	public function hasSerialisedProperties()
	{
		return count($this->serializedproperties) > 0;
	}

	public function getInitSerializedproperties()
	{
		return $this->initSerializedproperties === true;
	}


	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getSerializedClassMember()
	{
		return $this->serializedproperties;
	}

	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function hasI18NSerialisedProperties()
	{

		foreach ($this->serializedproperties as $property)
		{
			if ($property->isLocalized())
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getClassI18nMember()
	{
		$result = array();
		foreach ($this->getClassMember() as $property)
		{
			if (!$property->isLocalized())
			{
				continue;
			}

			$result[] = $property;
		}

		return $result;
	}

	/**
	 * @return String
	 */
	private function getModelVersion()
	{
		if (is_null($this->modelVersion))
		{
			return $this->getParentModel()->getModelVersion();
		}
		return $this->modelVersion;
	}

	/**
	 * @return String
	 */
	public function getPhpDefaultI18nValues()
	{
		$code = array()	;
		foreach ($this->getI18nClassMember() as $property)
		{
			if ($property->getName() == 'publicationstatus')
			{
				$code[] = '		$this->setPublicationstatus(\''. $this->getDefaultStatus() . '\');';
			}
			else
			{
				$value = $property->getPhpI18nDefaultValue();
				if (!is_null($value))
				{
					$code[] = $value;
				}
			}
		}

		foreach ($this->serializedproperties as $property)
		{
			if ($property->isLocalized())
			{
				$value = $property->getPhpI18nDefaultValue();
				if (!is_null($value))
				{
					$code[] = $value;
				}
			}
		}
		return join("\n", $code);
	}

	/**
	 * @return String
	 */
	public function getPhpDefaultValues()
	{
		$code = array()	;
		if ($this->hasParentModel())
		{
			$code[] = '		parent::setDefaultValues();';
			if ($this->getParentModel()->getDefaultStatus() != $this->getDefaultStatus())
			{
				$property = $this->getBaseProperty('publicationstatus');
				if (!$property->isLocalized())
				{
					$code[] = '		$this->setPublicationstatusInternal(\''. $this->getDefaultStatus() . '\');';
				}
			}
		}
		else
		{
			$property = $this->getPropertyByName('publicationstatus');
			if (!$property->isLocalized())
			{
				$code[] = '		$this->setPublicationstatusInternal(\''. $this->getDefaultStatus() . '\');';
			}
		}

		$code[] = '		$this->setModelversionInternal(\''. $this->getModelVersion() . '\');';

		foreach ($this->getPropertiesComplete() as $property)
		{
			if (array_search($property->getName(), array('id', 'model', 'lang', 'publicationstatus', 'modelversion')) !== false)
			{
				continue;
			}
			$value = $property->getPhpDefaultValue();
			if (!is_null($value))
			{
				$code[] = $value;
			}
		}

		foreach ($this->serializedproperties as $property)
		{
			$value = $property->getPhpDefaultValue();
			if (!is_null($value))
			{
				$code[] = $value;
			}
		}
		return join("\n", $code);
	}

	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getValidatesProperties()
	{
		$result = array();
		$properties = $this->getClassMember();
		$labelProperty = $this->getPropertyByName("label");
		$currentModel = $this;
		while ($currentModel !== null && $labelProperty === null)
		{
			$labelProperty = $currentModel->getPropertyByName("label");
			$currentModel = $currentModel->getParentModel();
		}
		if ($labelProperty !== null)
		{
			$properties[] = $labelProperty;
		}
		foreach ($properties as $property)
		{
			if (is_null($property->getConstraints()))
			{
				continue;
			}
			$result[] = $property;
		}
		return $result;
	}

	/**
	 * @return Boolean
	 */
	public function hasCascadeDelete()
	{
		foreach ($this->getClassMember() as $property)
		{
			if ($property->hasCascadeDelete())
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @return Boolean
	 */
	public function hasChildrenProperties()
	{
		return count($this->childrenProperties) > 0;
	}

	public function hasI18nLabelWithDefaultValue()
	{
		$prop = $this->getPropertyByName('label');
		return !is_null($prop) ? $prop->isLocalized() : false;
	}

	public function getI18nLabelDefaultValue()
	{
		return $this->getPropertyByName('label')->getPhpI18nDefaultValue();
	}
	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getI18nClassMember()
	{
		if ($this->hasParentModel())
		{
			$result = $this->getParentModel()->getI18nClassMember();
		}
		else
		{
			$result = array();
		}
		foreach ($this->getClassMember() as $property)
		{
			if (!$property->isLocalized())
			{
				continue;
			}
			$result[] = $property;
		}
		return $result;
	}

	/**
	 * @param String $extension
	 * @return String
	 */
	public function generateSQLScript($extension)
	{
		$generator = new builder_Generator('models');
		$generator->assign_by_ref('model', $this);
		if ($this->hasParentModel())
		{
			$result = $generator->fetch("TableExtend.".$extension.".sql.tpl");
		}
		else
		{
			$result = $generator->fetch("TableBase.".$extension.".sql.tpl");
		}
		return $result;
	}

	/**
	 * @param String $extension
	 * @return String
	 */
	public function generateDeleteSQLScript($extension)
	{
		if ($this->hasParentModel())
		{
			return null;
		}
		$generator = new builder_Generator('models');
		$generator->assign_by_ref('model', $this);
		return $generator->fetch("DropTableBase.".$extension.".sql.tpl");
	}

	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getTableField()
	{
		$result = array();
		foreach ($this->properties as $property)
		{
			if (array_search($property->getName(), array('id', 'model')) !== false 
				|| $property->isOverride() || !$property->getType())
			{
				continue;
			}
			$result[] = $property;
		}

		return $result;
	}

	/**
	 * @param String $extension
	 * @return String
	 */
	public function generateSQLI18nScript($extension)
	{
		$generator = new builder_Generator('models');
		$generator->assign_by_ref('model', $this);
		if ($this->hasParentModel())
		{
			$result = $generator->fetch("TableI18nExtend.".$extension.".sql.tpl");
		}
		else
		{
			$result = $generator->fetch("TableI18nBase.".$extension.".sql.tpl");
		}
		return $result;
	}

	/**
	 * @param String $extension
	 * @return String
	 */
	public function generateDeleteSQLI18nScript($extension)
	{
		if ($this->hasParentModel())
		{
			return null;
		}
		$generator = new builder_Generator('models');
		$generator->assign_by_ref('model', $this);
		return $generator->fetch("DropTableI18nBase.".$extension.".sql.tpl");
	}

	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getTableI18nField()
	{
		$result = array();
		foreach ($this->properties as $property)
		{
			if (array_search($property->getName(), array('id', 'model', 'lang')) !== false || $property->isOverride() || !$property->isLocalized())
			{
				continue;
			}
			$result[] = $property;
		}

		return $result;
	}

	// private methods

	/**
	 * @param DOMElement $xmlElement
	 */
	private function importAttributes($xmlElement)
	{
		foreach($xmlElement->attributes as $attribute)
		{
			$name = $attribute->nodeName;
			$value = $attribute->nodeValue;
			switch ($name)
			{
				case "extend":
					$this->extend = $value;
					break;
				case "localized":
					$this->localized = self::getBoolean($value);
					break;
				case "icon":
					$this->icon = $value;
					break;
				case "linked-to-root-module":
					$this->linkedToRootModule = self::getBoolean($value);
					break;
				case "has-url":
					$this->hasUrl = self::getBoolean($value);
					break;	
				case "use-rewrite-url":
					$this->useRewriteUrl = self::getBoolean($value);
					break;					
				case "indexable":
					$this->indexable = self::getBoolean($value);
					break;
				case "backoffice-indexable":
					$this->backofficeIndexable = self::getBoolean($value);
					break;
				case "model-version":
					$this->modelVersion = $value;
					break;
				case "table-name":
					$this->tableName = $value;
					break;
				case "table-name-oci":
					$this->tableNameOci = $value;
					break;
				case "classname":
					$this->className = $value;
					break;
				case "use-correction":
					$this->useCorrection = self::getBoolean($value);
					break;
				case "publish-on-day-change":
					$this->publishOnDayChange = self::getBoolean($value);
					break;
				case "xsi:schemaLocation":
					// just ignore it
					break;
				case "inject":
					$this->inject = self::getBoolean($value);
					break;
				default:
					self::addMessage("Obsolete document attribute ". $this->getName() . " : '". $name ."' =  $value");
					break;
			}
		}
	}

	/**
	 * @param DOMElement $xmlElement
	 */
	private function importProperties($xmlElement)
	{
		$nodeList = $xmlElement->getElementsByTagName('properties');
		if ($nodeList->length == 0)
		{
			$nodeList = $xmlElement->getElementsByTagName('components');
		}
		if ($nodeList->length > 0)
		{
			foreach ($nodeList->item(0)->childNodes as $xmlProperty)
			{
				if ($xmlProperty->nodeName == "add")
				{
					$property = new generator_PersistentProperty($this);
					$property->initialize($xmlProperty);
					$this->addProperty($property);
				}
			}
		}
	}

	/**
	 * @param generator_PersistentProperty $property
	 */
	private function addProperty($property)
	{
		$this->properties[$property->getId()] = $property;
	}


	/**
	 * @param DOMElement $xmlElement
	 */
	private function importSerializedProperties($xmlElement)
	{
		$nodeList = $xmlElement->getElementsByTagName('serializedproperties');
		if ($nodeList->length > 0)
		{
			foreach ($nodeList->item(0)->childNodes as $xmlProperty)
			{
				if ($xmlProperty->nodeName == "add")
				{
					$property = new generator_PersistentProperty($this);
					$property->setSerializedProperty(true);
					$property->initialize($xmlProperty);
					$this->addSerializedProperty($property);
				}
			}
		}
	}

	/**
	 * @param generator_PersistentProperty $property
	 */
	private function addSerializedProperty($property)
	{
		$this->serializedproperties[$property->getId()] = $property;
	}

	/**
	 * @param DOMElement $xmlElement
	 */
	private function importFormProperties($xmlElement)
	{
		$nodeList = $xmlElement->getElementsByTagName('form');
		if ($nodeList->length > 0)
		{
			foreach ($nodeList->item(0)->childNodes as $xmlProperty)
			{

				if ($xmlProperty->nodeName == "property")
				{
					$property = new generator_FormProperty($this);
					$property->initialize($xmlProperty);
					$this->formProperties[] = $property;
				}
			}
		}
	}


	/**
	 * @param DOMElement $xmlElement
	 */
	private function importChildrenProperties($xmlElement)
	{
		$nodeList = $xmlElement->getElementsByTagName('children');
		if ($nodeList->length > 0)
		{
			//Document fils uniquement stocké dans l'arbre
			foreach ($nodeList->item(0)->childNodes as $xmlProperty)
			{
				if ($xmlProperty->nodeName == "child")
				{
					$property = new generator_ChildrenProperty($this);
					$property->initialize($xmlProperty);
					$this->childrenProperties[] = $property;
				}
			}
		}
	}

	/**
	 * @param DOMElement $xmlElement
	 */
	private function importWorkflow($xmlElement)
	{
		$nodeList = $xmlElement->getElementsByTagName('workflow');
		if ($nodeList->length > 0)
		{
			$this->workflow = new generator_Workflow($this);
			$this->workflow->initialize($nodeList->item(0));
			if (f_util_StringUtils::isNotEmpty($this->workflow->getStartTask()))
			{
				$this->useCorrection = true;
			}
		}
	}

	/**
	 * @param DOMElement $xmlElement
	 */
	private function importPublicationStatus($xmlElement)
	{
		$nodeList = $xmlElement->getElementsByTagName('statuses');
		if ($nodeList->length > 0)
		{
			$statuses = $nodeList->item(0);
			if ($statuses->hasAttribute('default'))
			{
				$this->defaultStatus = $statuses->getAttribute('default');
			}

			if ($this->name == self::BASE_MODEL)
			{
				foreach ($statuses->childNodes as $status)
				{
					if ($status->nodeName == "add")
					{
						$name = $status->getAttribute('name');
						$this->statuses[$name] = $name;
					}
				}
			}
		}
	}
}

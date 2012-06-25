<?php
/**
 * @package framework.builder.generator
 */
class generator_PersistentModel
{
	const BASE_MODEL = 'modules_generic/Document';
	const BASE_CLASS_NAME = 'f_persistentdocument_PersistentDocument';

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
	 * children models
	 * @var generator_PersistentModel[]
	 */
	private $children = array();

	private $icon;
	private $hasUrl;
	private $useRewriteUrl;
	private $indexable;
	private $backofficeIndexable;
	private $modelVersion;

	private $tableName;
	
	private $dbMapping;

	private $useCorrection;

	private $defaultStatus;
	private $localized;

	private $usePublicationDates;

	/**
	 * @var generator_Workflow
	 */
	private $workflow;

	private $properties = array();
	private $serializedproperties = array();
	private $childrenProperties = array();
	private $inverseProperties = array();

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
		
		$injectionConfig = Framework::getConfigurationValue('injection/document', array());	
		foreach (ModuleService::getInstance()->getPackageNames() as $packageName)
		{
			list ( , $moduleName) = explode('_', $packageName);
			
			$dir = f_util_FileUtils::buildModulesPath($moduleName, 'persistentdocument');
			if (!is_dir($dir)) {continue;}
			
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
							$documentName = basename($pathinfo['basename'], ".xml");
							if (!isset($models[$packageName.'/'.$documentName]))
							{
								$xmlDoc = self::loadFile($filePath);
								if ($xmlDoc !== null)
								{
									$document = new generator_PersistentModel($xmlDoc, $moduleName, $documentName);
									if ($document->canBeCompile($injectionConfig))
									{
										$models[$document->getName()] = $document;
									}
									else 
									{
										self::addMessage("Document $moduleName / $documentName not valid for compilation");
									}
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
			/* @var $model generator_PersistentModel */
			if ($model->inject()) {continue;}
			
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
			/* @var $model generator_PersistentModel */
			if ($model->inject()) {continue;}
		
			$childrenNames = array();
			foreach ($model->getChildren() as $child)
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
			/* @var $model generator_PersistentModel */
			if ($model->inject() || !$model->usePublicationDates()) {continue;}
			
			$modelName = $model->getName();
			while ($model)
			{
				$pubproperty = $model->getPropertyByName('publicationstatus');
				if ($pubproperty !== null) {break;}
				$model = $model->getParentModel();
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
			/* @var $model generator_PersistentModel */
			foreach ($model->getProperties() as $property)
			{
				/* @var $property generator_PersistentProperty */
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
			/* @var $model generator_PersistentModel */
			if ($model->inject()) {continue;}
			
			$moduleName = strtoupper($model->getModuleName());
			$documentName = strtoupper($model->getDocumentName());
			$modelName = $model->getName();
	
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
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('indexableDocumentInfos.ser');
		f_util_FileUtils::writeAndCreateContainer($compiledFilePath, serialize($indexableDocumentInfos), f_util_FileUtils::OVERRIDE);		
	}
	
	public static function buildAllowedDocumentInfos()
	{
		$allowedDocumentInfos = array ('hasUrl' => array(), 'useRewriteUrl' => array());
		foreach (self::loadModels() as $model)
		{	
			/* @var $model generator_PersistentModel */
			if ($model->inject()) {continue;}
			
			if ($model->hasURL())
			{
				$modelName = $model->getName();
				$allowedDocumentInfos['hasUrl'][] = $modelName;
				if ($model->useRewriteURL())
				{
					$allowedDocumentInfos['useRewriteUrl'][] = $modelName;
				}
			}
		}
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('allowedDocumentInfos.ser');
		f_util_FileUtils::writeAndCreateContainer($compiledFilePath, serialize($allowedDocumentInfos), f_util_FileUtils::OVERRIDE);	
	}

	/**
	 * Generate build/project/modules/uixul/style/documenticons.css
	 * 
	 */
	public static function getCssBoDocumentIcon()
	{
		$iconsCSS = array();
		foreach (self::loadModels() as $model)
		{	
			/* @var $model generator_PersistentModel */
			if ($model->inject()) 
			{
				$moduleName = $model->getParentModel()->getModuleName();	
				$documentName = $model->getParentModel()->getDocumentName();	
			}
			else
			{
				$moduleName = $model->getModuleName();	
				$documentName = $model->getDocumentName();
			}
			$iconName = 'small/' . $model->getIcon();
			$selector = 'treechildren::-moz-tree-image(modules_'.$moduleName.'_'.$documentName.') {list-style-image: url(/changeicons/'.$iconName.'.png);}';
			if ($model->inject() || !isset($iconsCSS[$moduleName .'/'. $documentName]))
			{
				$iconsCSS[$moduleName .'/'. $documentName] = $selector;
			}

		}
		$documentIconsPath = f_util_FileUtils::buildChangeBuildPath('modules', 'uixul', 'style', 'documenticons.css');
		f_util_FileUtils::writeAndCreateContainer($documentIconsPath, implode(PHP_EOL, $iconsCSS), f_util_FileUtils::OVERRIDE);
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
		
		$xmlDoc = self::loadFile(f_util_FileUtils::buildFrameworkPath('persistentdocument','document.xml'));
		$virtualModel = new generator_PersistentModel($xmlDoc, 'generic', 'Document');
										
		// Set common properties.
		foreach ($models as $model)
		{
			/* @var $model generator_PersistentModel */
			if ($model->extend === null || $model->extend == self::BASE_MODEL)
			{
				$model->applyGenericDocumentModel($virtualModel);
			}
			else
			{
				if (!isset($models[$model->extend]))
				{
					throw new Exception("Could not find extended model ".$model->extend);
				}
			}
		}
		
		// Check Localisation and correction Constraints
		foreach ($models as $model)
		{
			if ($model->extend !== null)
			{
				if ($model->isLocalized() && !$models[$model->extend]->isLocalized())
				{
					throw new Exception("Can not render model ".$model->name." localized while ".$models[$model->extend]->name." is not");
				}
				
				if ($model->useCorrection && !$models[$model->extend]->useCorrection)
				{
					throw new Exception("Can not activate correction on ".$model->name." while not activated on ".$models[$model->extend]->name);
				}
				
				/* @var $extendedModel generator_PersistentModel */
				$extendedModel = $models[$model->extend];
				while ($extendedModel !== null)
				{
					$extendedModel->children[] = $model;
					if ($extendedModel->extend === null) {break;}
					$extendedModel = $models[$extendedModel->extend];
				}
			}
		}

		// Check heritage.
		foreach ($models as $model)
		{
			$model->checkOverrideProperties();			
			$model->generateS18sPropertyIfNeeded();
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
	
	private function generateS18sPropertyIfNeeded()
	{
		if (count($this->serializedproperties) > 0)
		{
			$localized = false;
			foreach ($this->serializedproperties as $property)
			{
				if ($property->isLocalized())
				{
					if (!$this->localized)
					{
						$property->setLocalized(false);
						self::addMessage('Unable to localize serialized property ' . $property->getName() . ' on non localized document ' . $this->getName());
					}
					else
					{
						$localized = true;
						break;
					}
				}
			}
			
			$prop = $this->getPropertyByName('s18s');
			if ($prop === null) 
			{
				$ancestor = $this->getAncestorPropertyByName('s18s');
				if ($ancestor === null)
				{
					$prop = generator_PersistentProperty::generateS18sProperty($this);
					$prop->setLocalized($localized);
					$this->addProperty($prop);
				}
				elseif (!$ancestor->isLocalized() && $localized)
				{
					$prop = generator_PersistentProperty::generateS18sProperty($this);
					$prop->setParentProperty($ancestor);
					$prop->setLocalized($localized);
				}
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

		$this->importChildrenProperties($xml);

		$this->importPublicationStatus($xml);

		$this->importWorkflow($xml);
	}
	
	public function canBeCompile($config)
	{
		$finalClassPath = f_util_FileUtils::buildModulesPath($this->moduleName, 'persistentdocument', $this->documentName . '.class.php');
		if (!file_exists($finalClassPath))
		{
			return false;
		}
		$serviceClassPath = f_util_FileUtils::buildModulesPath($this->moduleName, 'lib', 'services', ucfirst($this->documentName) . 'Service.class.php');
		if (!file_exists($serviceClassPath))
		{
			return false;
		}
		
		if ($this->inject())
		{
			$original = $this->extend;
			if (!$original || !isset($config[$original]) || $config[$original] != $this->name)
			{
				return false;
			}
		}
		return true;
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
	
	function inject()
	{
		return $this->inject === true;
	}
	
	/**
	 * @return Boolean
	 */
	public function hasParentModel()
	{
		return ($this->extend !== null);
	}
	
	/**
	 * @return generator_PersistentModel
	 */
	public function getParentModel()
	{
		return ($this->extend !== null) ? self::$m_models[$this->extend] : null; 
	}

	/**
	 * @return generator_PersistentModel
	 */
	public function getRootModel()
	{
		if ($this->hasParentModel())
		{
			return $this->getParentModel()->getRootModel();
		}
		return $this;
	}
	
	/**
	 * @return Boolean
	 */
	public function isParentModelBackofficeIndexable()
	{
		$parentModel = $this->getParentModel();
		return $parentModel !== null && $parentModel->isBackofficeIndexable();
	}
	

	public function getParentModelName()
	{
		$parentModel = $this->getParentModel();
		return $parentModel !== null ? $parentModel->getName() : null;
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

	/**
	 * @return true
	 */
	function useDocumentEditor()
	{
		return true;
	}
	
	/**
	 * @param String[] $models
	 * @return String[]
	 */
	public function getCompatibleModel($models = null)
	{
		if (is_null($models))
		{
			$models = array(self::BASE_MODEL);
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
	 * @param String $name
	 * @return generator_PersistentProperty
	 */
	function getPropertyByName($name)
	{
		return (isset($this->properties[$name])) ? $this->properties[$name] : null;
	}
	
	/**
	 * @param String $name
	 * @return generator_PersistentProperty
	 */
	function getAncestorPropertyByName($name)
	{
		$pm = $this->getParentModel();
		while ($pm !== null)
		{
			if (isset($pm->properties[$name])) {return $pm->properties[$name];}
			$pm = $pm->getParentModel();
		}
		return null;
	}	

	/**
	 * @param String $name
	 * @return generator_ChildrenProperty
	 */
	private function getChildrenPropertyByName($name)
	{
		return (isset($this->childrenProperties[$name])) ? $this->childrenProperties[$name] : null;
	}
	
	/**
	 * @param String $name
	 * @return generator_ChildrenProperty
	 */
	function getAncestorChildrenPropertyByName($name)
	{
		$pm = $this->getParentModel();
		while ($pm !== null)
		{
			if (isset($pm->childrenProperties[$name])) {return $pm->childrenProperties[$name];}
			$pm = $pm->getParentModel();
		}
		return null;
	}	
	

	/**
	 * @param String $name
	 * @return generator_PersistentProperty
	 */
	private function getSerializedPropertyByName($name)
	{
		return (isset($this->serializedproperties[$name])) ? $this->serializedproperties[$name] : null;
	}
	
	/**
	 * @param String $name
	 * @return generator_PersistentProperty
	 */
	function getAncestorSerializedPropertyByName($name)
	{
		$pm = $this->getParentModel();
		while ($pm !== null)
		{
			if (isset($pm->serializedproperties[$name])) {return $pm->serializedproperties[$name];}
			$pm = $pm->getParentModel();
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
		
		$this->tableName = f_persistentdocument_PersistentProvider::getInstance()
			->getSchemaManager()->generateSQLModelTableName($this->moduleName, $this->documentName, $this->dbMapping);		
		$props = $this->properties;
		
		$this->properties = array();		
		foreach ($baseDocument->properties as $propertyName => $property)
		{
			/* @var $property generator_PersistentProperty */
			if (isset($props[$propertyName]))
			{
				/* @var $newProperty generator_PersistentProperty */
				$newProperty = $props[$propertyName];
				$newProperty->mergeGeneric($property);
				unset($props[$propertyName]);
			}
			else
			{
				$newProperty = clone($property);
				$newProperty->setModel($this);
			}
			$this->properties[$propertyName] = $newProperty;
		}
		foreach ($props as $propertyName => $property) 
		{
			$this->properties[$propertyName] = $property;
		}
		$props = null;

		if ($this->defaultStatus === null)
		{
			$this->defaultStatus = $baseDocument->defaultStatus;
		}

		if ($this->backofficeIndexable === null)
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

		if ($this->usePublicationDates === null)
		{
			$this->usePublicationDates = $baseDocument->usePublicationDates;
		}
		
		if ($this->icon === null)
		{
			$this->icon = $baseDocument->icon;
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

		/**
		 * Serialized Properties
		 */
		if (count($this->serializedproperties) > 0)
		{
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
			$property = generator_PersistentProperty::generateS18sProperty($this);
			$property->setLocalized($localized);
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
		$parentModel = $this->getParentModel();
		if ($parentModel === null) {return;}
		$parentModel->checkOverrideProperties();

		$this->tableName = $parentModel->tableName;
		$this->localized = $parentModel->localized;
		
		//$this->useCorrection = $parentModel->useCorrection;
				
		foreach ($this->properties as $name => $property)
		{
			$parentProperty = $this->getAncestorPropertyByName($name);
			if ($parentProperty !== null)
			{
				$property->setParentProperty($parentProperty);
			}
		}

		foreach ($this->childrenProperties as $name => $property)
		{
			$parentProperty = $this->getAncestorChildrenPropertyByName($name);
			if ($parentProperty !== null)
			{
				$property->setParentProperty($parentProperty);
			}
		}
		
		foreach ($this->serializedproperties as $name => $property)
		{
			$parentProperty = $this->getAncestorSerializedPropertyByName($name);
			if ($parentProperty !== null)
			{
				$property->setParentProperty($parentProperty);
			}
		}
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
	 * @return String
	 */
	public function getDefaultStatus()
	{
		if ($this->defaultStatus === null && $this->hasParentModel())
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
		if ($this->useCorrection === null && $this->hasParentModel())
		{
			return $this->getParentModel()->hasCorrection();
		}
		return $this->useCorrection;
	}

	/**
	 * @return Boolean
	 */
	public function usePublicationDates()
	{
		if ($this->usePublicationDates === null && $this->hasParentModel())
		{
			return $this->getParentModel()->usePublicationDates();
		}
		else
		{
			return $this->usePublicationDates;
		}
	}

	/**
	 * @return generator_Workflow
	 */
	private function getWorkflow()
	{
		if ($this->workflow === null && $this->hasParentModel())
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
	
	public function getBaseModelClassName()
	{
		if ($this->hasParentModel())
		{
			return $this->getParentModel()->getDocumentClassName() . 'model';
		}
		return 'f_persistentdocument_PersistentDocumentModel';		
	}
	
	public function getExtendI18nClassName()
	{
		if ($this->hasParentModel())
		{
			return  'extends ' . $this->getParentModel()->getDocumentClassName() . 'I18n';
		}
		return 'implements f_persistentdocument_I18nPersistentDocument';		
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
	public function getIcon()
	{
		if (empty($this->icon) && $this->hasParentModel())
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
		$generator->assign_by_ref('model', $this);
		$result = $generator->fetch('DocumentModel.tpl');
		return $result;
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
	public function generatePhpI18nClass()
	{
		$generator = new builder_Generator('models');
		$generator->assign_by_ref('model', $this);
		$result = $generator->fetch('DocumentI18nClass.tpl');
		return $result;
	}

	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getClassMember()
	{
		$result = array();
		$exp =  array('id', 'model', 'lang', 'label');
		foreach ($this->getPropertiesComplete() as $property)
		{
			if (in_array($property->getName(), $exp) || $property->isOverride())
			{
				continue;
			}
				
			$result[] = $property;
		}
		return $result;
	}
	
	public function hasClassMembers()
	{
		return count($this->getClassMember()) > 0;
	}
	
	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getScalarClassMember()
	{
		$result = array();
		$exp =  array('id', 'model', 'lang', 'label');
		foreach ($this->getPropertiesComplete() as $property)
		{
			if (in_array($property->getName(), $exp) || $property->isOverride() || $property->isDocument())
			{
				continue;
			}	
			$result[] = $property;
		}
		return $result;		
	}
	
	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getDocumentClassMember()
	{
		$result = array();
		foreach ($this->getPropertiesComplete() as $property)
		{
			if ($property->isOverride() || !$property->isDocument())
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

	/**
	 * @return boolean
	 */
	public function getInitSerializedproperties()
	{
		$prop = $this->getPropertyByName('s18s');
		return ($prop !== null && !$prop->isOverride());
	}
	
	/**
	 * @return boolean
	 */
	public function getInitI18nSerializedproperties()
	{
		$prop = $this->getPropertyByName('s18s');
		return ($prop !== null && $prop->isLocalized());
	}	

	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getSerializedClassMember()
	{
		$result = array();
		foreach ($this->serializedproperties as $property) 
		{
			if ($property->isDocument()) {continue;}
			$result[] = $property; 
		}
		return $result;
	}
	
	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getSerializedDocumentClassMember()
	{
		$result = array();
		foreach ($this->serializedproperties as $property) 
		{
			if ($property->isDocument())
			{
				$result[] = $property; 
			}
		}
		return $result;
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
	public function getPhpDefaultValues()
	{
		$code = array()	;
		if ($this->hasParentModel())
		{
			$code[] = '		parent::setDefaultValues();';
			if ($this->getParentModel()->getDefaultStatus() != $this->getDefaultStatus())
			{	
				$property = $this->getPropertyByName('publicationstatus');
				if ($property === null) {$property = $this->getAncestorPropertyByName('publicationstatus');}
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
	 * @param boolean $addSelf
	 * @return string
	 */
	private function getValidatesPropertyNamesToExclude($addSelf = false)
	{
		if ($this->hasParentModel())
		{
			$array = $this->getParentModel()->getValidatesPropertyNamesToExclude(true);
		}
		else
		{
			$array = array('id', 'lang', 'model');
		}
		if ($addSelf)
		{
			foreach ($this->getPropertiesComplete() as $property) 
			{
				if ($property->hasDefinedConstraints() || $property->isRequired() || $property->getMaxOccurs() > 1)
				{
					if (!in_array($property->getName(), $array))
					{
						$array[] = $property->getName();
					}
				}
			}
			
			foreach ($this->getSerializedProperties() as $property)
			{
				if ($property->hasDefinedConstraints() || $property->isRequired() || $property->getMaxOccurs() > 1)
				{
					if (!in_array($property->getName(), $array))
					{
						$array[] = $property->getName();
					}
				}				
			}
		}
		return $array;
	}

	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getValidatesProperties()
	{
		$result = array();
		$alreadyValidated = $this->getValidatesPropertyNamesToExclude();
		foreach ($this->getPropertiesComplete() as $property)
		{
			if ($property->hasDefinedConstraints() || $property->isRequired() || $property->getMaxOccurs() > 1)
			{
				if (!in_array($property->getName(), $alreadyValidated))
				{
					$result[] = $property;
				}
			}
		}
		foreach ($this->getSerializedProperties() as $property)
		{
			if ($property->hasDefinedConstraints() || $property->isRequired() || $property->getMaxOccurs() > 1)
			{
				if (!in_array($property->getName(), $alreadyValidated))
				{
					$result[] = $property;
				}
			}				
		}
		return $result;
	}
	
	/**
	 * @return boolean
	 */
	public function hasValidatesProperties()
	{
		return count($this->getValidatesProperties()) > 0;
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
	
	/**
	 * @return String
	 */
	public function getPhpDefaultI18nValues()
	{
		$code = array()	;
		$labelProp = $this->getPropertyByName('label');
		if (!is_null($labelProp) && $labelProp->isLocalized())
		{
			$value = $labelProp->getPhpI18nDefaultValue();
			if (!is_null($value))
			{
				$code[] = $value;
			}
		}
		
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
		return (count($code)) ? join(PHP_EOL, $code) : '';
	}
	/**
	 * @return array<generator_PersistentProperty>
	 */
	public function getI18nClassMember()
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
					$this->extend = (trim($value) !== '') ? trim($value) : null;
					break;
				case "localized":
					$this->localized = self::getBoolean($value);
					break;
				case "icon":
					$this->icon = $value;
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
					$this->dbMapping = $value;
					break;
				case "use-correction":
					$this->useCorrection = self::getBoolean($value);
					break;
				case "use-publication-dates":
					$this->usePublicationDates = self::getBoolean($value);
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
		if ($nodeList->length > 0)
		{
			foreach ($nodeList->item(0)->childNodes as $xmlProperty)
			{
				if ($xmlProperty->nodeName == "property" || $xmlProperty->nodeName == "add")
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
		$this->properties[$property->getName()] = $property;
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
				if ($xmlProperty->nodeName == "property" || $xmlProperty->nodeName == "add")
				{
					$property = new generator_PersistentProperty($this);
					$property->setModelPart(generator_PersistentProperty::SERIALISED_PROPERTY);
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
		$this->serializedproperties[$property->getName()] = $property;
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
					$this->childrenProperties[$property->getName()] = $property;
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
		}
	}
}
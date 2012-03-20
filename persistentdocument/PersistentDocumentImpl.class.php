<?php
/**
 * @package framework.persistentdocument
 */
abstract class f_persistentdocument_PersistentDocumentImpl implements f_persistentdocument_PersistentDocument, f_mvc_Bean
{
	private $m_persistentState;

	/**
	 * @var integer
	 */
	private $m_id = 0;
	
	/**
	 * @var integer
	 */
	private $m_treeId;
	
	/**
	 * @var integer
	 */
	private $m_providerId;
	
	/**
	 * @var I18nInfo
	 */
	private $m_i18nInfo;

	/**
	 * @var array
	 */
	protected $validationErrors;

	/**
	 * @var array
	 */
	private $modifiedProperties = array();

	/**
	 * @var array
	 */
	private $modifiedPropertyValues = array();

	/**
	 * @var array<Integer, f_persistentdocument_PersistentDocument>
	 */
	private $m_documentInverse;

	/**
	 * @var Boolean
	 */
	private $is_i18InfoModified = false;

	/**
	 * @var If something indicates it wants to insert the document in tree, is it possible ?
	 */
	private $insertInTree = true;

	/**
	 * @var Integer
	 */
	private $_parentNodeId;

	/**
	 * @var array<String,String|String[]>
	 */
	private $m_metas;
	
	/**
	 * @var Boolean
	 */
	private $metasModified = false;

	/**
	 * @var f_persistentdocument_I18nPersistentDocument
	 */
	private $i18nVoObject;

	/**
	 * @param Integer $id
	 * @param I18nInfo $i18nInfo
	 * @param Integer $treeId
	 */
	public function __construct($id = 0, $i18nInfo = null, $treeId = null)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug("__construct I($id) T($treeId)". get_class($this));
		}
		$this->m_id = intval($id);
		if ($treeId !== null)
		{
			$this->m_treeId = intval($treeId);
		}
		
		if (is_null($i18nInfo))
		{
			$i18nInfo = new I18nInfo();
			$i18nInfo->setVo($this->getContextLang());
		}

		$this->m_i18nInfo = $i18nInfo;

		if ($id > 0)
		{
			$this->setDocumentPersistentState(self::PERSISTENTSTATE_INITIALIZED);
		}
		else
		{
			$this->setDocumentPersistentState(self::PERSISTENTSTATE_NEW);
			$this->setDefaultValues();
			if (count($this->modifiedProperties) > 0)
			{
				$this->modifiedProperties = array();
				$this->modifiedPropertyValues = array();
			}
		}
	}
	
	/**
	 * Revert document properties values from PersistentDocumentArray to integer before
	 * to serialize documents for cache storage. Empty here, called by child classes.
	 */
	protected function __cleanDocumentPropertiesForSleep()
	{
		// nothing
	}
	
	/**
	 * @return String[] the property names to be serialized. Other properties will be ignored 
	 */
	protected function __getSerializedPropertyNames()
	{
		return array("\0".__CLASS__."\0m_id", "\0".__CLASS__."\0m_treeId",
		 "\0".__CLASS__."\0m_i18nInfo", "\0".__CLASS__."\0m_persistentState",
		 "\0".__CLASS__."\0i18nVoObject");
	}

	/**
	 * Used by provider where document inserted in tree
	 * @param Integer $treeId or null
	 */
	public function setProviderTreeId($treeId)
	{
		$this->m_treeId = $treeId;
	}
	
	/**
	 * @return Integer the count of available langs, after deleting
	 */
	function removeContextLang ()
	{
		$this->is_i18InfoModified = true;
		$contextLang = $this->getContextLang();

		$this->getI18nInfo()->removeLabel($contextLang);
		//Si on supprime la vo on réattribut la vo a la premiere traduction dispo
		$labels = $this->getI18nInfo()->getLabels();
		$labelCount = count($labels);
		if ($labelCount > 0 && $this->getLang() == $contextLang)
		{
			$lang = key($labels);
			if ($lang != $contextLang)
			{
				foreach ($this->getPersistentModel()->getPropertiesInfos() as $name => $property)
				{
					$this->propertyUpdated($name);
				}
				$this->setLang($lang);
			}
		}

		return $labelCount;
	}

	/**
	 * @param Boolean $insertInTree
	 */
	public function setInsertInTree($insertInTree)
	{
		$this->insertInTree = $insertInTree;
	}

	public final function setParentNodeId($parentNodeId)
	{
		$this->_parentNodeId = $parentNodeId;
	}

	public final function getParentNodeId()
	{
		return $this->_parentNodeId;
	}

	/**
	 * @see f_persistentdocument_PersistentDocument::getTreeId()
	 *
	 * @return Integer or NULL
	 */
	public function getTreeId()
	{
		return $this->m_treeId;
	}
	
	/**
	 * @return Boolean
	 */
	public function canInsertInTree()
	{
		return $this->insertInTree;
	}

	/**
	 * @return void
	 */
	public function __destruct()
	{
		$this->m_i18nInfo = null;
		$this->validationErrors = null;
		$this->modifiedProperties = null;
		$this->modifiedPropertyValues = null;
		$this->m_documentInverse = null;
	}

	/**
	 * @return void
	 */
	private function setIsPersisted()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : ' . $this->__toString());
		}

		$this->modifiedProperties = array();
		$this->modifiedPropertyValues = array();
		$this->is_i18InfoModified = false;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	protected final function addDocumentInverse($document)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : ' . $this->__toString() . ' add ' . $document->__toString());
		}
		$this->checkLoaded();

		if (is_null($this->m_documentInverse))
		{
			$this->m_documentInverse = array();
		}

		$this->m_documentInverse[$document->getId()] = $document;
		$this->propertyUpdated('documentinverse');
	}

	/**
	 * @return void
	 */
	public final function saveDocumentsInverse()
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : ' . $this->__toString());
		}
		if (!is_null($this->m_documentInverse))
		{
			foreach ($this->m_documentInverse as $documentId =>$document)
			{
				if ($document->isModified())
				{
					$document->save();
				}
			}
			$this->m_documentInverse = null;
		}
	}

	/**
	 * Set the default properties value
	 */
	protected function setDefaultValues()
	{
	}


	/**
	 * @return f_mvc_BeanModel
	 */
	function getBeanModel()
	{
		return $this->getPersistentModel();
	}

	/**
	 * @return String the parent node label | null
	 */
	function getParentNodeLabel()
	{
		$parentDocument = TreeService::getInstance()->getParentDocument($this);
		if ($parentDocument === null)
		{
			return null;
		}
		return $parentDocument->getLabel();
	}

	/**
	 * @return String the path of the document, ie all the ancestors labels separated by '/' | null
	 */
	function getPath()
	{
		$path = $this->getDocumentService()->getPathOf($this, '/');
		if (f_util_StringUtils::isEmpty($path))
		{
			return null;
		}
		return $path;
	}

	/**
	 * @return String
	 */
	protected final function getContextLang()
	{
		return RequestContext::getInstance()->getLang();
	}

	/**
	 * @return I18nInfo
	 */
	public final function getI18nInfo()
	{
		return $this->m_i18nInfo;
	}

	/**
	 * @return Boolean
	 */
	public function isLocalized()
	{
		return false;
	}

	/**
	 * @return f_persistentdocument_I18nPersistentDocument
	 */
	protected function getI18nVoObject()
	{
		if ($this->i18nVoObject === null)
		{
			$this->i18nVoObject = $this->getProvider()->getI18nDocument($this, $this->getLang(), true);
		}
		return $this->i18nVoObject;
	}
	
	/**
	 * For the use of PersistentProvider
	 * @return f_persistentdocument_I18nPersistentDocument
	 */
	public function getRawI18nVoObject()
	{
		return $this->i18nVoObject;
	}

	/**
	 * @internal For PersistentProvider usage only
	 * @param f_persistentdocument_I18nPersistentDocument $i18VoObject
	 * @return void
	 */
	public function setI18nVoObject($i18nVoObject)
	{
		$this->i18nVoObject = $i18nVoObject;
	}

	/**
	 * @param string $lang null for the current context lang
	 * @return f_persistentdocument_I18nPersistentDocument
	 */
	protected function getI18nObject($lang = null)
	{
		if ($lang === null)
		{
			$lang = $this->getContextLang();
		}

		if ($lang === $this->getLang())
		{
			return $this->getI18nVoObject();
		}
		return $this->getProvider()->getI18nDocument($this, $lang);
	}

	/**
	 * @return Boolean
	 */
	public function isContextLangAvailable()
	{
		if (!$this->isLocalized())
		{
			return true;
		}

		return $this->getI18nInfo()->isContextLangAvailable();
	}

	/**
	 * @return Boolean
	 */
	public function isLangAvailable($lang)
	{
		if (!$this->isLocalized())
		{
			return true;
		}
		return $this->getI18nInfo()->isLangAvailable($lang);
	}

	//
	// Methodes de gestion de la persistance du document
	//

	/**
	 * @internal ONLY for PersistentProvider usage
	 * @return unknown
	 */
	public function getDocumentPersistentState()
	{
		return $this->m_persistentState;
	}

	/**
	 * @internal ONLY for PersistentProvider usage
	 * @param unknown_type $newValue
	 */
	public function setDocumentPersistentState($newValue)
	{
		$newValue = intval($newValue);
		if ($newValue < self::PERSISTENTSTATE_NEW  || $newValue > self::PERSISTENTSTATE_DELETED)
		{
			$newValue = self::PERSISTENTSTATE_NEW;
		}
		if ($newValue !== $this->m_persistentState)
		{
			$this->m_persistentState = $newValue;
			switch ($this->m_persistentState)
			{
				case self::PERSISTENTSTATE_LOADED:
					$this->setIsPersisted();
					break;
				case self::PERSISTENTSTATE_MODIFIED:
					if (Framework::isDebugEnabled())
					{
						Framework::debug("Document modified ". $this->getId() . "(" . get_class($this) .")");
					}
					break;
			}
		}
	}

	/**
	 * @internal ONLY for PersistentProvider usage
	 * @param Boolean $loadAll if all data must be retrieved (by default)
	 * @return array
	 */
	public function getDocumentProperties($loadAll = true)
	{
		$propertyBag = array();
		$propertyBag['id'] = $this->m_id;
		$propertyBag['model'] = $this->getDocumentModelName();
		$propertyBag['lang'] = $this->getI18nInfo()->getVo();
		$propertyBag['label'] = $this->getI18nInfo()->getVoLabel();
		return $propertyBag;
	}

	/**
	 * @internal ONLY for PersistentProvider usage
	 * @param array $propertyBag
	 */
	public function setDocumentProperties($propertyBag)
	{
		if (array_key_exists('id', $propertyBag))
		{
			$this->m_id = intval($propertyBag['id']);
		}
		if (array_key_exists('lang', $propertyBag))
		{
			$this->getI18nInfo()->setVo($propertyBag['lang']);
		}
		if (array_key_exists('label', $propertyBag))
		{
			$this->getI18nInfo()->setVoLabel($propertyBag['label']);
		}
	}

	/**
	 * @internal ONLY for PersistentProvider usage
	 * @param int $id
	 */
	function updateId($id)
	{
		$this->m_id = intval($id);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array<String> $propertyNames
	 * @param boolean $mergeArrayProperties
	 */
	public function mergeWith($document, $propertyNames, $mergeArrayProperties = false)
	{
		$this->checkLoaded();

		$selfProperties = $this->getDocumentProperties();
		$newProperties = $document->getDocumentProperties();
		foreach ($propertyNames as $propertyName)
		{
			$selfValues = $selfProperties[$propertyName];
			$newValues = $newProperties[$propertyName];
			if ($selfValues instanceof f_persistentdocument_PersistentDocumentArray)
			{
				if ($mergeArrayProperties)
				{
					$selfValues->mergeWith($newValues);
				}
				else
				{
					$selfValues->replaceWith($newValues);
				}
			}
			else
			{
				$selfValues = $newValues;
			}
			$selfProperties[$propertyName] = $selfValues;
		}
		$this->setDocumentProperties($selfProperties);
		if ($this->getPersistentModel()->isLocalized())
		{
			$this->getI18nVoObject()->setDocumentProperties($selfProperties);
		}
		if (self::PERSISTENTSTATE_LOADED == $this->getDocumentPersistentState())
		{
			$this->setDocumentPersistentState(self::PERSISTENTSTATE_MODIFIED);
		}
	}

	//
	// Gestion des données du document
	//

	/**
	 * Obtient l'id du document
	 *
	 * @return Integer
	 */
	public function getId()
	{
		return $this->m_id;
	}

	/**
	 * Obtient l'id du bean
	 *
	 * @return Integer
	 */
	public function getBeanId()
	{
		return $this->getId();
	}

	/**
	 * @return String
	 */
	public final function getLang()
	{
		return $this->getI18nInfo()->getVo();
	}

	/**
	 * @param String $lang
	 * @return void
	 */
	public final function setLang($lang)
	{
		$oldLang = $this->getI18nInfo()->getVo();
		if ($oldLang != $lang)
		{
			$this->checkLoaded();
			$this->getI18nInfo()->setVo($lang);
			$this->propertyUpdated('lang');
			$this->is_i18InfoModified = true;
			$this->i18nVoObject = null;
		}
	}

	/**
	 * @param String $label
	 * @return void
	 */
	public function setLabel($label)
	{
		$this->checkLoaded();
		if ($this->setLabelInternal($label))
		{
			$this->propertyUpdated('label');
		}
	}

	/**
	 * @return Boolean
	 */
	protected function setLabelInternal($label)
	{
		if ($this->isLocalized())
		{
			$update = $this->getI18nObject()->setLabel($label);
			if ($update)
			{
				$this->is_i18InfoModified = true;
				if ($this->getI18nInfo()->getVo() == $this->getContextLang())
				{
					$this->getI18nInfo()->setVoLabel($label);
				}
				else
				{
					$this->getI18nInfo()->setLabel($this->getContextLang(), $label);
				}
				return true;
			}
		}
		else
		{
			$oldLablel = $this->getI18nInfo()->getVoLabel();
			if ($oldLablel != $label)
			{
				$this->is_i18InfoModified = true;
				$this->getI18nInfo()->setVoLabel($label);
				return true;
			}
		}

		return false;
	}

	/**
	 * @return Boolean
	 */
	public function isI18InfoModified()
	{
		return $this->is_i18InfoModified;
	}

	/**
	 * @return String
	 */
	public function getLabel()
	{
		if ($this->isLocalized())
		{
			return $this->getI18nObject()->getLabel();
		}
		else
		{
			return $this->getI18nInfo()->getVoLabel();
		}
	}

	/**
	 * @return String
	 */
	public function getLabelAsHtml()
	{
		return f_util_HtmlUtils::textToHtml($this->getLabel());
	}

	/**
	 * Define the label of the tree node of the document.
	 * By default, this method returns the label property value.
	 * @return String
	 */
	public function getTreeNodeLabel()
	{
		return $this->getDocumentService()->getTreeNodeLabel($this);
	}
	
	/**
	 * @return string
	 */
	public function getNavigationLabel()
	{
		return $this->getDocumentService()->getNavigationLabel($this);
	}
	
	/**
	 * @return string
	 */
	public function getNavigationLabelAsHtml()
	{
		return f_util_HtmlUtils::textToHtml($this->getNavigationLabel());
	}

	/**
	 * @return String
	 */
	public function getVoLabel()
	{
		return $this->getI18nInfo()->getVoLabel();
	}

	/**
	 * @param String $lang
	 * @return String
	 */
	public function getLabelForLang($lang)
	{
		if ($this->isLocalized())
		{
			return $this->getI18nObject($lang)->getLabel();
		}
		else
		{
			return $this->getI18nInfo()->getVoLabel();
		}
	}
	
	/**
	 * @return Boolean
	 */
	public function hasCorrection()
	{
		return $this->getPersistentModel()->useCorrection() && $this->getCorrectionid() > 0;
	}
	
	/**
	 * @return Boolean
	 */
	public function isCorrection()
	{
		return $this->getPersistentModel()->useCorrection() && $this->getCorrectionofid() > 0;
	}

	/**
	 * @return void
	 */
	public final function load()
	{
		if ($this->getDocumentPersistentState() == self::PERSISTENTSTATE_INITIALIZED ||
		$this->getDocumentPersistentState() == self::PERSISTENTSTATE_MODIFIED)
		{
			$this->loadDocument();
		}
	}

	/**
	 * Save PersistentDocument in database.
	 */
	public final function save($parentNodeId = null)
	{
		$this->getDocumentService()->save($this, $parentNodeId);
	}

	/**
	 * persist only metastring field in database
	 */
	function saveMeta()
	{
		$this->getDocumentService()->saveMeta($this);
	}

	/**
	 * @return void
	 */
	public final function deactivate()
	{
		$this->getDocumentService()->deactivate($this->getId());
	}

	/**
	 * @return void
	 */
	public final function activate()
	{
		$this->getDocumentService()->activate($this->getId());
	}

	/**
	 * validate document and return boolean result
	 * @return boolean
	 */
	public function isValid()
	{
		$this->validationErrors = new validation_Errors();
		return true;
	}

	/**
	 * @param String $message
	 */
	public function addValidationError($message)
	{
		$this->validationErrors[] = $message;
	}

	/**
	 * delete PersistentDocument in Database
	 */
	public final function delete()
	{
		$this->getDocumentService()->delete($this);
	}

	/**
	 * Load relations items
	 * @internal Only for PersistentProvider usage
	 */
	public function preCascadeDelete()
	{
	}

	/**
	 * Delete relations items
	 * @internal Only for PersistentProvider usage
	 */
	public function postCascadeDelete()
	{
	}

	/**
	 * @return f_persistentdocument_PersistentProvider
	 */
	public function getProvider()
	{
		return $this->getDocumentService()->getProvider();
	}
	
	/**
	 * set providerId
	 * @param string $providerId
	 */
	function setProviderId($providerId)
	{
		$this->m_providerId = $providerId;
	}

	protected function checkLoaded()
	{
		if ($this->m_persistentState === self::PERSISTENTSTATE_INITIALIZED)
		{
			$this->loadDocument();
		}

		return $this->getDocumentPersistentState() != self::PERSISTENTSTATE_INITIALIZED;
	}
	
	abstract protected function resetDocumentProperties();

	/**
	 * @param String $name
	 * @param mixed $value
	 * @param String $lang
	 */
	protected final function setOldValue($name, $value, $lang = null)
	{
		if ($lang !== null)
		{
			if (!isset($this->modifiedPropertyValues[$name]))
			{
				$this->modifiedPropertyValues[$name] = array($lang => $value);
			}
			elseif (!array_key_exists($lang, $this->modifiedPropertyValues[$name]))
			{
				$this->modifiedPropertyValues[$name][$lang] = $value;
			}
		}
		elseif (!array_key_exists($name, $this->modifiedPropertyValues))
		{
			$this->modifiedPropertyValues[$name] = $value;
		}
	}

	/**
	 * @param String $name
	 * @param String $lang
	 * @return mixed
	 */
	protected final function getOldValue($name, $lang = null)
	{
		if ($lang !== null)
		{
			if (isset($this->modifiedPropertyValues[$name]) && array_key_exists($lang, $this->modifiedPropertyValues[$name]))
			{
				return $this->modifiedPropertyValues[$name][$lang];
			}
		}
		elseif (array_key_exists($name, $this->modifiedPropertyValues))
		{
			return $this->modifiedPropertyValues[$name];
		}
		return null;
	}

	/**
	 * @internal used by DocumentService only
	 * @return array<String => mixed>
	 */
	public final function getOldValues()
	{
		return $this->modifiedPropertyValues;
	}

	/**
	 * @internal used by DocumentService only
	 * @param array<String => mixed> $oldValues
	 */
	public final function setOldValues($oldValues = array())
	{
		$this->modifiedPropertyValues = $oldValues;
	}

	protected final function propertyUpdated($propertyName)
	{
		if ($this->getDocumentPersistentState() == self::PERSISTENTSTATE_LOADED)
		{
			$this->setDocumentPersistentState(self::PERSISTENTSTATE_MODIFIED);
		}
		if (!array_key_exists($propertyName, $this->modifiedProperties))
		{
			$this->modifiedProperties[$propertyName] = true;
		}
		$this->propertyChanged($propertyName);
	}

	/**
	 * Called everytime a property has changed.
	 *
	 * @param string $propertyName Name of the property that has changed.
	 */
	protected function propertyChanged($propertyName)
	{

	}

	/**
	 * @param String $propertyName
	 * @return boolean
	 */
	public function isPropertyModified($propertyName)
	{
		return isset($this->modifiedProperties[$propertyName]);
	}

	/**
	 * @return array<String>
	 */
	public function getModifiedPropertyNames()
	{
		return array_keys($this->modifiedProperties);
	}

	/**
	 * @param array<String> $modifiedPropertyNames
	 */
	public function setModifiedPropertyNames($modifiedPropertyNames = array())
	{
		$this->modifiedProperties = array();
		foreach ($modifiedPropertyNames as $name)
		{
			$this->modifiedProperties[$name] = true;
		}
		$this->is_i18InfoModified = isset($this->modifiedProperties["lang"]) || isset($this->modifiedProperties["label"]);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $b
	 * @return boolean
	 */
	public function equals($b)
	{
		return DocumentHelper::equals($this, $b);
	}

	/**
	 * @return String
	 */
	public function __toString()
	{
		return $this->getDocumentModelName().' '.$this->getId();
	}

	/**
	 * @return boolean
	 */
	public function isNew()
	{
		return self::PERSISTENTSTATE_NEW === $this->m_persistentState;
	}

	/**
	 * @return boolean
	 */
	public function isModified()
	{
		return self::PERSISTENTSTATE_MODIFIED === $this->m_persistentState;
	}

	/**
	 * @return boolean
	 */
	public function isDeleted()
	{
		return self::PERSISTENTSTATE_DELETED === $this->m_persistentState;
	}

	/**
	 * @return validation_Errors
	 */
	public function getValidationErrors()
	{
		return $this->validationErrors;
	}

	/**
	 * @return void
	 */
	protected final function loadDocument()
	{
		$this->getProvider()->loadDocument($this);
	}


	/**
	 * @param f_persistentdocument_PersistentDocumentImpl $sourceDocument
	 */
	function copyMutateSource($sourceDocument)
	{
		$this->m_id = $sourceDocument->m_id;
		$this->m_i18nInfo = $sourceDocument->m_i18nInfo;
		$this->m_treeId = $sourceDocument->m_treeId;
		$this->setDocumentPersistentState(self::PERSISTENTSTATE_MODIFIED);
		unset($this->modifiedProperties['documentversion']);
	}

	/**
	 * @param Boolean $recursive
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function duplicate($recursive = false)
	{
		$duplicate = $this->getDocumentService()->getNewDocumentInstance();
		$this->transfertProperties($duplicate, $recursive, true);
		return $duplicate;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Boolean $copyToVo
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function copyTo($document, $copyToVo = true)
	{
		return $this->copyPropertiesTo($document, $copyToVo);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Boolean $copyToVo
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function copyPropertiesTo($document, $copyToVo = true)
	{
		$this->transfertProperties($document, false, $copyToVo);
		return $document;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array<String> $propertiesNames
	 * @param Boolean $copyToVo
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function copyPropertiesListTo($document, $propertiesNames, $copyToVo = true)
	{
		$model = $this->getPersistentModel();
		$destModel = $document->getPersistentModel();

		if ($copyToVo)
		{
			$contextLang = $this->getContextLang();
			$document->setLang($contextLang);
			$document->setLabel($this->getLabel());
		}

		//Local copy of properties
		foreach ($propertiesNames as $propertyName)
		{
			//System property ignored
			if ($propertyName == 'id' || $propertyName == 'model' || $propertyName == 'lang')
			{
				continue;
			}
			$propertyInfo = $model->getProperty($propertyName);

			//Invalid source or destination property Ignored
			if (is_null($propertyInfo) || is_null($destModel->getProperty($propertyName)))
			{
				continue;
			}

			$this->transfertProperty($propertyInfo, $document, false);
		}
		return $document;
	}

	/**
	 * Copy properties values for the current context lang to the document
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Boolean $transfertToVo
	 * @param Boolean $recursive
	 */
	private function transfertProperties($document, $recursive = false, $transfertToVo = true)
	{
		$model = $this->getPersistentModel();
		$destModel = $document->getPersistentModel();

		if ($transfertToVo)
		{
			$contextLang = $this->getContextLang();
			$document->setLang($contextLang);
			$document->setLabel($this->getLabel());
		}

		//Local copy of properties
		$properties = $model->getPropertiesInfos();
		foreach ($properties as $propertyName => $propertyInfo)
		{
			//System property ignored
			if ($propertyName == 'id' || $propertyName == 'model' || $propertyName == 'lang' || $propertyName == 'documentversion')
			{
				continue;
			}

			//Invalid destination property Ignored
			if (is_null($destModel->getProperty($propertyName)))
			{
				continue;
			}

			$this->transfertProperty($propertyInfo, $document, $recursive);
		}
	}

	/**
	 * @param PropertyInfo $propertyInfo
	 * @param f_persistentdocument_PersistentDocumentImpl $document
	 * @param Boolean $recursive
	 */
	private function transfertProperty($propertyInfo, $document, $recursive)
	{
		$propertyName = ucfirst($propertyInfo->getName());

		if (!$propertyInfo->isDocument())
		{
			$value = $this->{'get'.$propertyName}();
			$document->{'set'.$propertyName}($value);
		}
		else
		{
			if (!$propertyInfo->isArray())
			{
				$value = $this->{'get'.$propertyName}();
				if (!is_null($value) && $recursive)
				{
					$document->{'set'.$propertyName}($value->duplicate($recursive));
				}
				else
				{
					$document->{'set'.$propertyName}($value);
				}
			}
			else
			{
				$document->{'removeAll'.$propertyName}();
				foreach ($this->{'get'.$propertyName.'Array'}() as $value)
				{
					if ($recursive)
					{
						$document->{'add'.$propertyName}($value->duplicate($recursive));

					}
					else
					{
						$document->{'add'.$propertyName}($value);
					}
				}
			}
		}
	}

	/**
	 * @return boolean true if the document is published
	 */
	public final function isPublished()
	{
		return 'PUBLICATED' == $this->getPublicationstatus();
	}


	// Metadata management

	/**
	 * @param String $name
	 * @return Boolean
	 */
	public function hasMeta($name)
	{
		$this->initMetas();
		return isset($this->m_metas[$name]);
	}

	/**
	 * @param String $name
	 * @return String[]
	 */
	public function getMetaMultiple($name)
	{
		$this->initMetas();
		return isset($this->m_metas[$name]) ? $this->m_metas[$name] : array();
	}

	/**
	 * @param String $name
	 * @return String
	 */
	public function getMeta($name)
	{
		$this->initMetas();
		return isset($this->m_metas[$name]) ? $this->m_metas[$name] : null;
	}

	/**
	 * @param String $name
	 * @param String|String[] $value
	 */
	public function setMeta($name, $value)
	{
		$this->initMetas();
		if ($value === null)
		{
			unset($this->m_metas[$name]);
		}
		else
		{
			$this->m_metas[$name] = $value;
		}
		$this->metasModified = true;
	}

	/**
	 * @param String $name
	 * @param String[] $values
	 */
	public function setMetaMultiple($name, $values)
	{
		$this->initMetas();
		if ($values === null)
		{
			unset($this->m_metas[$name]);
		}
		elseif (!is_array($values))
		{
			throw new Exception(__METHOD__.": bad argument. ".var_export($values, true)." is not an array.");
		}
		$this->m_metas[$name] = $values;
		$this->metasModified = true;
	}

	/**
	 * @param String $name
	 * @param String $value
	 */
	public function addMetaValue($name, $value)
	{
		$this->initMetas();
		if (!isset($this->m_metas[$name]))
		{
			$this->m_metas[$name] = array();
		}
		elseif (!is_array($this->m_metas[$name]))
		{
			throw new Exception("Try to add meta value ($value) for a mono-valued metadata ($name) on document ".$this->m_id);
		}
		$this->m_metas[$name][] = $value;
		$this->metasModified = true;
	}

	/**
	 * @param String $name
	 * @param String $value
	 * @return Boolean if value was founded
	 */
	public function hasMetaValue($name, $value)
	{
		$this->initMetas();
		if (!isset($this->m_metas[$name]))
		{
			return false;
		}
		$values = $this->m_metas[$name];
		if (!is_array($values))
		{
			throw new Exception("Asked for hasMetaValue ($value) for a mono-valued metadata ($name) on document ".$this->m_id);
		}
		return array_search($value, $values) !== false;
	}

	/**
	 * Remove a value from a multi-valued meta
	 * @param String $name
	 * @param String $value
	 * @return Boolean true if meta value was founded
	 */
	public function removeMetaValue($name, $value)
	{
		$this->initMetas();
		if (!isset($this->m_metas[$name]))
		{
			return false;
		}
		$values = $this->m_metas[$name];
		if (!is_array($values))
		{
			throw new Exception("Try to remove a meta value ($value) for a mono-valued metadata ($name) on document ".$this->m_id);
		}
		$key = array_search($value, $values);
		if ($key === false)
		{
			return false;
		}
		unset($values[$key]);
		if (count($values) == 0)
		{
			unset($this->m_metas[$name]);
		}
		else
		{
			$this->m_metas[$name] = $values;
		}
		$this->metasModified = true;
		return true;
	}

	/**
	 * Fill metastring field if some meta was modified (ie. call to one of the setMetaXX() methods)
	 */
	public function applyMetas()
	{
		if ($this->metasModified)
		{
			if (f_util_ArrayUtils::isEmpty($this->m_metas))
			{
				$this->setMetastring(null);
			}
			else
			{
				$this->setMetastring(serialize($this->m_metas));
			}
			$this->metasModified = false;
		}
	}

	/**
	 * Make sure $this->metas is initialized properly
	 */
	private function initMetas()
	{
		if ($this->m_metas === null)
		{
			$metaString = $this->getMetastring();
			if ($metaString !== null)
			{
				$this->m_metas = unserialize($metaString);
			}
			else
			{
				$this->m_metas = array();
			}
		}
	}
		
	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0) use isPublished()
	 */
	public final function isPublicated()
	{
		return $this->isPublished();
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getPersistentModel()
	 */
	function getDocumentModel()
	{
		return $this->getPersistentModel();
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getModifiedPropertyNames
	 */
	public function getModifiedProperties()
	{
		return $this->getModifiedPropertyNames();
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use <YourDocumentService>::addTreeAttributes
	 */
	public function addTreeAttributesCompatibility($moduleName, $treeType, &$nodeAttributes)
	{
		if (f_util_ClassUtils::methodExists($this, 'addTreeAttributes'))
		{
			$this->addTreeAttributes($moduleName, $treeType, $nodeAttributes);
		}
	}
}
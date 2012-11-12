<?php
/**
 * @deprecated use \Change\Documents\AbstractDocument
 */
abstract class f_persistentdocument_PersistentDocument implements f_mvc_Bean
{
	
	const PERSISTENTSTATE_NEW = 0;
	const PERSISTENTSTATE_INITIALIZED = 2;
	const PERSISTENTSTATE_LOADED = 3;
	const PERSISTENTSTATE_MODIFIED = 4;
	const PERSISTENTSTATE_DELETED = 5;
		
	const PROPERTYTYPE_BOOLEAN = 'Boolean';
	const PROPERTYTYPE_INTEGER = 'Integer';
	const PROPERTYTYPE_DOUBLE = 'Double';
	const PROPERTYTYPE_DATETIME = 'DateTime';
	const PROPERTYTYPE_STRING = 'String';
	const PROPERTYTYPE_LOB = 'Lob';
	const PROPERTYTYPE_LONGSTRING = 'LongString';
	const PROPERTYTYPE_XHTMLFRAGMENT = 'XHTMLFragment';
	const PROPERTYTYPE_DOCUMENT = 'Document';
	const PROPERTYTYPE_DOCUMENTARRAY = 'DocumentArray';
	
	const PROPERTYTYPE_DECIMAL = 'Decimal';
	const PROPERTYTYPE_JSON = 'JSON';
	const PROPERTYTYPE_BBCODE = 'BBCode';
	const PROPERTYTYPE_OBJECT = 'Object';
	const PROPERTYTYPE_DOCUMENTID = 'DocumentId';
	
	const PROPERTYTYPE_STRING_DEAFULT_MAX_LENGTH = 255;
	
	const STATUS_DRAFT = 'DRAFT';
	const STATUS_CORRECTION = 'CORRECTION';
	const STATUS_ACTIVE = 'ACTIVE';
	const STATUS_PUBLISHED = 'PUBLISHED';
	const STATUS_DEACTIVATED = 'DEACTIVATED';
	const STATUS_FILED = 'FILED';
	const STATUS_DEPRECATED = 'DEPRECATED';
	const STATUS_TRASH = 'TRASH';
	const STATUS_WORKFLOW = 'WORKFLOW';
	
	
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
	 * get Document Model name
	 *
	 * @return string 'modules_<module_name>/<document_name>'
	 */
	abstract public function getDocumentModelName();

	/**
	 * @return f_persistentdocument_DocumentService
	 */
	abstract public function getDocumentService();

	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	abstract public function getPersistentModel();

	/**
	 * @param integer $id
	 * @param I18nInfo $i18nInfo
	 * @param integer $treeId
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
	 * @return string[] the property names to be serialized. Other properties will be ignored 
	 */
	protected function __getSerializedPropertyNames()
	{
		return array("\0".__CLASS__."\0m_id", "\0".__CLASS__."\0m_treeId",
		 "\0".__CLASS__."\0m_i18nInfo", "\0".__CLASS__."\0m_persistentState",
		 "\0".__CLASS__."\0i18nVoObject");
	}

	/**
	 * Used by provider where document inserted in tree
	 * @param integer $treeId or null
	 */
	public function setProviderTreeId($treeId)
	{
		$this->m_treeId = $treeId;
	}
	
	/**
	 * @return integer the count of available langs, after deleting
	 */
	public function removeContextLang()
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
	 * @param boolean $insertInTree
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
	 * @return integer or NULL
	 */
	public function getTreeId()
	{
		return $this->m_treeId;
	}
	
	/**
	 * @return boolean
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
		$this->i18nVoObject = null;
		$this->validationErrors = null;
		$this->modifiedProperties = null;
		$this->modifiedPropertyValues = null;
		$this->m_documentInverse = null;
		$this->m_metas = null;
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
	 * @return string the parent node label | null
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
	 * @return string the path of the document, ie all the ancestors labels separated by '/' | null
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
	 * @return string
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
	 * @return boolean
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
	 * @return boolean
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
	 * @return boolean
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
	 * @param boolean $loadAll if all data must be retrieved (by default)
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
			if (is_array($selfValues))
			{
				if ($mergeArrayProperties)
				{
					$selfValues = array_unique(array_merge($selfValues, $newValues));
				}
				else
				{
					$selfValues = $newValues;
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
	 * @return integer
	 */
	public function getId()
	{
		return $this->m_id;
	}

	/**
	 * Obtient l'id du bean
	 *
	 * @return integer
	 */
	public function getBeanId()
	{
		return $this->getId();
	}

	/**
	 * @return string
	 */
	public final function getLang()
	{
		return $this->getI18nInfo()->getVo();
	}

	/**
	 * @param string $lang
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
	 * @param string $label
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
	 * @return boolean
	 */
	protected function setLabelInternal($label)
	{
		$label = $label === null ? null : strval($label);
		
		if ($this->isLocalized())
		{
			$labels = $this->getI18nInfo()->getLabels();
			$lang = $this->getContextLang();
			$oldLabel = isset($labels[$lang]) ? $labels[$lang] : null;
			if ($oldLabel !== $label)
			{
				$this->is_i18InfoModified = true;
				$this->getI18nInfo()->setLabel($lang, $label);
				$this->getI18nObject($lang)->setLabel($label);
				return true;
			}
		}
		else
		{
			$oldLablel = $this->getI18nInfo()->getVoLabel();
			if ($oldLablel !== $label)
			{
				$this->is_i18InfoModified = true;
				$this->getI18nInfo()->setVoLabel($label);
				return true;
			}
		}
		return false;
	}

	/**
	 * @return boolean
	 */
	public function isI18InfoModified()
	{
		return $this->is_i18InfoModified;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		if ($this->isLocalized())
		{
			$labels = $this->getI18nInfo()->getLabels();
			$lang = $this->getContextLang();
			return isset($labels[$lang]) ? $labels[$lang] : null;
		}
		else
		{
			return $this->getI18nInfo()->getVoLabel();
		}
	}

	/**
	 * @return string
	 */
	public function getLabelAsHtml()
	{
		return f_util_HtmlUtils::textToHtml($this->getLabel());
	}

	/**
	 * Define the label of the tree node of the document.
	 * By default, this method returns the label property value.
	 * @return string
	 */
	function getTreeNodeLabel()
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
	 * @return string
	 */
	public function getVoLabel()
	{
		return $this->getI18nInfo()->getVoLabel();
	}

	/**
	 * @param string $lang
	 * @return string
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
	 * @return boolean
	 */
	public function hasCorrection()
	{
		return $this->getPersistentModel()->useCorrection() && $this->getCorrectionid() > 0;
	}
	
	/**
	 * @return boolean
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

	private $propertiesErrors;
	
	/**
	 * validate document and return boolean result
	 * @return boolean
	 */
	public function isValid()
	{
		$this->propertiesErrors = null;
		return true;
	}

	/**
	 * @return boolean
	 */
	public function hasPropertiesErrors()
	{
		return is_array($this->propertiesErrors) && count($this->propertiesErrors);
	}
	
	/**
	 * @return array<propertyName => string[]>
	 */
	public function getPropertiesErrors()
	{
		if ($this->hasPropertiesErrors())
		{
			return $this->propertiesErrors;
		}
		return array();
	}
	
	/**
	 * @param string $propertyName
	 * @param string[] $errors
	 */
	public function addPropertyErrors($propertyName, $errors)
	{
		if (is_string($errors))
		{
			$errors = array($errors);
		}
		if (is_array($errors) && count($errors))
		{
			if (!$this->hasPropertiesErrors())
			{
				$this->propertiesErrors = array($propertyName => $errors);
			}
			elseif (isset($this->propertiesErrors[$propertyName]))
			{
				$this->propertiesErrors[$propertyName] = array_merge($this->propertiesErrors[$propertyName], $errors);
			}
			else
			{
				$this->propertiesErrors[$propertyName] = $errors;
			}
		}
	}	
	
	public function clearPropertyErrors($propertyName = null)
	{
		if ($propertyName === null)
		{
			$this->propertiesErrors = null;
		}
		elseif (is_array($this->propertiesErrors) && isset($this->propertiesErrors[$propertyName]))
		{
			unset($this->propertiesErrors[$propertyName]);
		}
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
		return f_persistentdocument_PersistentProvider::getInstance();
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
	
	/**
	 * @param string $name
	 * @param mixed $value
	 * @param string $lang
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
	 * @param string $name
	 * @param string $lang
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
	 * @param string $propertyName
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
	 * @return string
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
	 * @return void
	 */
	protected final function loadDocument()
	{
		$this->getProvider()->loadDocument($this);
	}


	/**
	 * @param f_persistentdocument_PersistentDocument $sourceDocument
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
	 * @param boolean $recursive
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
	 * @param boolean $copyToVo
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
	 * @param boolean $copyToVo
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
	 * @param boolean $transfertToVo
	 * @param boolean $recursive
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
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param boolean $recursive
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
		return 'PUBLISHED' == $this->getPublicationstatus();
	}


	// Metadata management

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasMeta($name)
	{
		$this->initMetas();
		return isset($this->m_metas[$name]);
	}

	/**
	 * @param string $name
	 * @return string[]
	 */
	public function getMetaMultiple($name)
	{
		$this->initMetas();
		return isset($this->m_metas[$name]) ? $this->m_metas[$name] : array();
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function getMeta($name)
	{
		$this->initMetas();
		return isset($this->m_metas[$name]) ? $this->m_metas[$name] : null;
	}

	/**
	 * @param string $name
	 * @param string|String[] $value
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
	 * @param string $name
	 * @param string[] $values
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
	 * @param string $name
	 * @param string $value
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
	 * @param string $name
	 * @param string $value
	 * @return boolean if value was founded
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
	 * @param string $name
	 * @param string $value
	 * @return boolean true if meta value was founded
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
	
	
	
	
	
	// DEPRECATED
	/**
	* @param string $property Nom de la propriété à atteindre
	* @return mixed|null
	*/
	public function __get($property)
	{
		if ($property === 'validationErrors')
		{
			Framework::error('Call to deleted ' . get_class($this) . '->' . $property . ' property');
			$v = new validation_Errors();
			$v->setDocument($this);
			return $v;
		}
		return null;
	}
	
	

	public function __call($name, $args)
	{
		switch ($name)
		{
			case 'addValidationError':
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . ' method');
				$this->addPropertyErrors('unknow', $args[0]);
				return;
			case 'getValidationErrors':
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . ' method');
				$v = new validation_Errors();
				$v->setDocument($this);
				return $v;
			default:
				throw new BadMethodCallException('No method ' . get_class($this) . '->' . $name);
		}
	}
}
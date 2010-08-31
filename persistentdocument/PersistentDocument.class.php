<?php
/**
 * PersistentDocument provide Document instance linked to Database through PersistentProvider
 * @package framework.persistentdocument
 */
interface f_persistentdocument_PersistentDocument
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
	
	const PROPERTYTYPE_STRING_DEAFULT_MAX_LENGTH = 255;
	
	const STATUS_DRAFT = 'DRAFT';
	const STATUS_CORRECTION = 'CORRECTION';
	const STATUS_ACTIVE = 'ACTIVE';
	const STATUS_PUBLISHED = 'PUBLICATED';
	const STATUS_DEACTIVATED = 'DEACTIVATED';
	const STATUS_FILED = 'FILED';
	const STATUS_DEPRECATED = 'DEPRECATED';
	const STATUS_TRASH = 'TRASH';
	const STATUS_WORKFLOW = 'WORKFLOW';

	/**
	 *
	 * @return f_persistentdocument_PersistentDocumentModel instance
	 */
	function getPersistentModel();

	/**
	 * @deprecated use getPersistentModel()
	 * @return f_persistentdocument_PersistentDocumentModel instance
	 */
	function getDocumentModel();
	
	/**
	 * get Document Properties from PersistentProvider
	 * @internal For framework internal usage only
	 * @param Boolean $loadAll if all data must be retrieved (by default)
	 * @return array ($propertyName => $propertyValue)
	 */
	function getDocumentProperties($loadAll = true);

	/**
	 * set Document Properties for PersistentProvider
	 * @internal For framework internal usage only
	 * @param array ($propertyName => $propertyValue,$propertyName => $propertyValue)
	 */
	function setDocumentProperties($documentProperties);

	/**
	 * get Document Model name
	 *
	 * @return string 'modules_<module_name>/<document_name>'
	 */
	function getDocumentModelName();

	/**
	 * get Database Table name where Document is stored
	 * @return string
	 * @deprecated Use DocumentModel->getTableName()
	 *
	 */
	function getDatabaseTableName();

	/**
	 * get Document Persistent State
	 * @see PERSISTENTSTATE_NEW, PERSISTENTSTATE_INITIALIZE, PERSISTENTSTATE_LOADED, PERSISTENTSTATE_MODIFIED,PERSISTENTSTATE_DELETED
	 */
	function getDocumentPersistentState();
	
	
	/**
	 * set providerId
	 * @param string $providerId
	 */
	function setProviderId($providerId);

	/**
	 * set Document Persistent State
	 * @param const PERSISTENTSTATE_NEW, PERSISTENTSTATE_INITIALIZE, PERSISTENTSTATE_LOADED, PERSISTENTSTATE_MODIFIED,PERSISTENTSTATE_DELETED
	 */
	function setDocumentPersistentState($newValue);
	
	/**
	 * L'identifient de l'arbre ou ce trouve le document ou NULL
	 * @return Integer
	 */
	public function getTreeId();
	
	/**
	 * @param Boolean $insertInTree
	 */
	public function setInsertInTree($insertInTree);

	/**
	 * If something wants to insert the document in tree, is it finally possible ?
	 * @return Boolean
	 */
	function canInsertInTree();

	/**
	 * @internal used by DocumentService
	 * Save modified Inverse document
	 */
	function saveDocumentsInverse();

	/**
	 * get Document Id
	 *
	 * @return Integer
	 */
	function getId();

	/**
	 * @return I18nInfo
	 */
	function getI18nInfo();

	/**
	 * @return Boolean
	 */
	function isLocalized();

	/**
	 * @return Boolean
	 */
	function isContextLangAvailable();

	/**
	 * @return Boolean
	 */
	function isLangAvailable($lang);
	
	/**
	 * @return String
	 */
	function getLang();

	/**
	 * @param String $lang
	 * @return void
	 */
	function setLang($lang);
	
	/**
	 * @return Integer the count of available langs, after deleting
	 */
    function removeContextLang();
	
	/**
	 * @return String
	 */
	function getLabel();
	
	/**
	 * @return String
	 */
	function getLabelAsHtml();
	
	/**
	 * Define the label of the tree node of the document.
	 * By default, this method returns the label property value.
	 * @return String
	 */
	function getTreeNodeLabel();
	
	/**
	 * @param String $label
	 * @return void
	 */
	function setLabel($label);
	

	/**
	 * validate document and return boolean result
	 * @return boolean
	 * @TODO manage a validation error stack
	 */
	function isValid();

	/**
	 * load PersistentDocument from Database
	 *
	 */
	function load();

	/**
	 * @return f_persistentdocument_DocumentService
	 */
	function getDocumentService();

	/**
	 *  save PersistentDocument in Database
	 */
	function save($parentNodeId = null);
	
	/**
	 * persist only metastring field in database
	 */
	function saveMeta();

	/**
	 * delete PersistentDocument in Database
	 */
	function delete();

	/**
	 * @return String the parent node label | null
	 */
	function getParentNodeLabel();
	
	/**
	 * @return String the path of the document, ie all the ancestors labels separated by '/' | null
	 */
	function getPath();
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array<String> $propertyNames
	 * @param boolean $mergeArrayProperties
	 */
	function mergeWith($document, $propertyNames, $mergeArrayProperties = false);

	/**
	 * check if 'id property' and 'class name' of $this PersistentDocument & $anotherPersistentDocument are equals
	 *
	 * @param f_persistentdocument_PersistentDocument $anotherPersistentDocument
	 * @return Boolean
	 */
	function equals($anotherPersistentDocument);

	/**
	 * @return boolean
	 */
	function isNew();

	/**
	 * @return boolean
	 */
	function isModified();

	/**
	 * @return boolean
	 */
	function isDeleted();

	/**
	 * @deprecated use isPublished()
	 * @return boolean
	 */
	function isPublicated();

	/**
	 * @return boolean
	 */
	function isPublished();
	
	/**
	 * @return Boolean
	 */
	public function hasCorrection();
	
	/**
	 * @return Boolean
	 */
	public function isCorrection();

	/**
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string>
	 */
	function buildTreeAttributes($moduleName, $treeType, &$nodeAttributes);


	/**
	 * Retourne un nouveau document du meme type et y duplique la valeur de toutes les propriété pour
	 * la langue en cours.
	 * @param Boolean $recursive
	 * @return f_persistentdocument_PersistentDocument new document, not identified (save must be called if you want to have a persisted copy of the original document)
	 */
	function duplicate($recursive = false);

	/**
	 * Copie toutes les valeurs des propriétés dans le document pour la langue en cours.
	 * @deprecated use copyPropertiesTo instead
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Boolean $copyToVo
	 * @return f_persistentdocument_PersistentDocument $document, updated
	 */
	function copyTo($document, $copyToVo = true);

	/**
	 * Copie toutes les valeurs des propriétés dans le document pour la langue en cours.
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Boolean $copyToVo
	 * @return f_persistentdocument_PersistentDocument $document, updated
	 */
	function copyPropertiesTo($document, $copyToVo = true);

	/**
	 * Copie toutes les valeurs des propriétés listé dans le document.
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array<String> $propertiesNames Noms des propriétés a copier
	 * @param Boolean $copyToVo les valeurs sont copier en temps que vo
	 * @return f_persistentdocument_PersistentDocument
	 */
	function copyPropertiesListTo($document, $propertiesNames, $copyToVo = true);

	/**
	 * @param String $propertyName
	 * @return boolean
	 */
	function isPropertyModified($propertyName);

	/**
	 * @return array<String>
	 */
	public function getModifiedPropertyNames();

	/**
	 * @return Boolean
	 */
	public function isI18InfoModified();
	
	/**
	 * @return indexer_BackofficeIndexedDocument
	 */
	public function getBackofficeIndexedDocument();
	
	/**
	 * @param String $name
	 * @return Boolean
	 */
	public function hasMeta($name);
	
	/**
	 * @param String $name
	 * @return String[]
	 */
	public function getMetaMultiple($name);
	
	/**
	 * @param String $name
	 * @return String
	 */
	public function getMeta($name);
	
	/**
	 * @param String $name
	 * @param String|String[] $value
	 */
	public function setMeta($name, $value);
	
	/**
	 * @param String $name
	 * @param String[] $values
	 */
	public function setMetaMultiple($name, $values);
	
	/**
	 * @param String $name
	 * @param String $value
	 */
	public function addMetaValue($name, $value);
	
	/**
	 * @param String $name
	 * @param String $value
	 */
	public function removeMetaValue($name, $value);
	
	/**
	 * @param String $name
	 * @param String $value
	 * @return Boolean if value was founded
	 */
	public function hasMetaValue($name, $value);
}
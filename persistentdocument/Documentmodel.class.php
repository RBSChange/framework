<?php
/**
 * generic_persistentdocument_documentmodel
 * @package generic
 */
class generic_persistentdocument_documentmodel extends f_persistentdocument_PersistentDocumentModel
{

	/**
	 * Constructor of generic_persistentdocument_documentmodel
	 */
	protected function __construct()
	{
		parent::__construct();
	}
	
	protected function loadProperties()
	{
		parent::loadProperties();
		$p = new PropertyInfo('id');
		$p->setDbTable('f_document')->setDbMapping('document_id')->setType('Integer')->setRequired(true);
		$this->m_properties[$p->getName()] = $p;
		$p = new PropertyInfo('model');
		$p->setDbTable('f_document')->setDbMapping('document_model')->setType('String')->setRequired(true);
		$this->m_properties[$p->getName()] = $p;
	}
	
	static function getGenericDocumentPropertiesNames()
	{
		return array("id", "model", "label", "author", "authorid", "creationdate", 
					"modificationdate", "publicationstatus", "lang", "modelversion", "documentversion",
					"startpublicationdate", "endpublicationdate", "metastring");
	}
	
	/**
	 * @return String
	 */
	public final function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * @return String
	 */
	public final function getIcon()
	{
		return 'document';
	}
	
	/**
	 * @return string 'modules_generic/document'
	 */
	public final function getName()
	{
		return f_persistentdocument_PersistentDocumentModel::BASE_MODEL;
	}

	/**
	 * @return string|NULL For example: modules_generic/reference
	 */
	public final function getBaseName()
	{
		return null;
	}

	/**
	 * @return string
	 */
	public final function getLabel()
	{
		return 'document';
	}

	/**
	 * @return string
	 */
	public final function getLabelKey()
	{
		return 'f.persistentdocument.general.document';
	}
	
	/**
	 * @return string For example: generic
	 */
	public final function getModuleName()
	{
		return 'generic';
	}

	/**
	 * @return string For example:folder
	 */
	public final function getDocumentName()
	{
		return 'Document';
	}

	/**
	 * @return string
	 */
	public final function getTableName()
	{
		return 'f_document';
	}	

	/**
	 * @return boolean
	 */
	public final function isLocalized()
	{
		return false;
	}
		
	/**
	 * @return boolean
	 */
	public final function isIndexable()
	{
		return false;
	}
	
	/**
	 * @return string[]
	 */
	public final function getAncestorModelNames()
	{
		return array();
	}

	/**
	 * @return String
	 */
	public final function getDefaultNewInstanceStatus()
	{
		return 'DRAFT';
	}
	
	/**
	 * Return if the document has 2 special properties (correctionid, correctionofid)
	 * @return Boolean
	 */	
	public final function useCorrection()
	{
		return false;
	}
	
	/**
	 * @return Boolean
	 */		
	public final function hasWorkflow()
	{
		return false;
	}
	
	/**
	 * @return String
	 */	
	public final function getWorkflowStartTask()
	{
		return null;
	}
	
	/**
	 * @return array<String, String>
	 */
	public final function getWorkflowParameters()
	{
		return array();
	}
	
	/**
	 * @return Boolean
	 */
	public function usePublicationDates()
	{
		return false;
	}
	
	/**
	 * @see f_persistentdocument_PersistentDocumentModel::getDocumentService()
	 *
	 * @return f_persistentdocument_DocumentService
	 */
	public function getDocumentService()
	{
		return f_persistentdocument_DocumentService::getInstance();
	}
	
	/**
	 * @return Boolean
	 */
	public final function hasURL()
	{
		return false;
	}
	
	/**
	 * @return Boolean
	 */
	public final function useRewriteURL()
	{
		return false;
	}
}
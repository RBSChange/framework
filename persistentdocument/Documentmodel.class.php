<?php
/**
 * generic_persistentdocument_Documentmodel
 * @package generic
 */
class generic_persistentdocument_Documentmodel extends f_persistentdocument_PersistentDocumentModel
{
	/**
	 * Constructor of generic_persistentdocument_Documentmodel
	 */
	protected function __construct()
	{
		parent::__construct($this->getName());
		$this->m_statuses = array('DRAFT','CORRECTION','ACTIVE','PUBLICATED','DEACTIVATED','FILED','DEPRECATED','TRASH','WORKFLOW',);
	}
	
	protected final function loadProperties()
	{
		$this->m_properties = array(
			'id' => new PropertyInfo('id', 'Integer', 1, 1, 'document_id', 'f_document', 
				true, false, false, false, false, null, 'blank:false', false, false, false, null),
			'model' => new PropertyInfo('model', 'String', 1, 1, 'document_model', 'f_document', 
				false, false, false, false, false, null, 'blank:false;maxSize:255', false, false, false, null));
	}
	
	protected final function loadSerialisedProperties()
	{
		$this->m_serialisedproperties = array();	
	}
	
	protected final function loadInvertProperties()
	{
		$this->m_invertProperties = array();
	}	
	
	protected final function loadChildrenProperties()
	{
		$this->m_childrenProperties = array();
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
	 * @return String
	 * @example modules_generic/Document
	 */
	public final function getName()
	{
		return 'modules_generic/Document';
	}

	/**
	 * @return String
	 * @example modules_generic/reference or null
	 */
	public final function getBaseName()
	{
		return null;
	}

	/**
	 * @return String
	 */
	public final function getLabel()
	{
		return 'document';
	}

	/**
	 * @return String
	 */
	public final function getLabelKey()
	{
		return 'f.persistentdocument.general.document';
	}
	
	/**
	 * @return String
	 * @example generic
	 */
	public final function getModuleName()
	{
		return 'generic';
	}

	/**
	 * @return String
	 * @example folder
	 */
	public final function getDocumentName()
	{
		return 'Document';
	}

	/**
	 * @return String
	 */
	public final function getTableName()
	{
		return 'f_document';
	}	

	/**
	 * @return Boolean
	 */
	public final function isLocalized()
	{
		return false;
	}
	
	/**
	 * @return Boolean
	 */
	public final function isLinkedToRootFolder()
	{
		return false;
	}
	
	/**
	 * @return Boolean
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
	public function publishOnDayChange()
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
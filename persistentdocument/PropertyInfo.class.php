<?php
/**
 * @package framework.persistentdocument
 */
class PropertyInfo
{
	private $m_name;
	private $m_type;
	private $m_minOccurs;
	private $m_maxOccurs;
	private $m_dbMapping;
	private $m_dbTable;
	private $m_primaryKey;
	private $m_cascadeDelete;
	private $m_treeNode;
	private $m_isArray;
	private $m_isDocument;
	private $m_defaultValue;
	private $m_constraints;
	private $m_isLocalized;
	private $m_isIndexed;
	private $m_hasSpecificIndex;
	private $m_fromList;


	/**
	 * Constructor of PropertyInfo
	 */
	function __construct($name, $type, $minOccurs, $maxOccurs, $dbMapping, $dbTable, $primaryKey, $cascadeDelete, $treeNode,
						$Array, $Document, $defaultValue, $constraints, $localized, $indexed, $specificIndex, $fromList)
	{
		$this->m_name = $name;
		$this->m_type = $type;
		$this->m_minOccurs = $minOccurs;
		$this->m_maxOccurs = $maxOccurs;
		$this->m_dbMapping = $dbMapping;
		$this->m_dbTable = $dbTable;
		$this->m_primaryKey = $primaryKey;
		$this->m_cascadeDelete = $cascadeDelete;
		$this->m_treeNode = $treeNode;
		$this->m_isArray = $Array;
		$this->m_isDocument = $Document;
		$this->m_defaultValue = $defaultValue;
		$this->m_constraints = $constraints;
		$this->m_isLocalized = $localized;
		$this->m_isIndexed = $indexed;
		$this->m_hasSpecificIndex = $specificIndex;
		$this->m_fromList = $fromList;
	}

	/**
	 * Returns the property's name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->m_name;
	}

	/**
	 * Returns the property's type.
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->m_type;
	}

	/**
	 * Returns the type of subdocuments with the slash replaced by an underscore
	 * for use on the backoffice side.
	 *
	 * @return string
	 */
	public function getTypeForBackofficeWidgets()
	{
		return f_persistentdocument_PersistentDocumentModel::convertModelNameToBackoffice($this->m_type);
	}

	/**
	 * Indicates whether the document property accepts documents of type $type.
	 *
	 * @return boolean
	 */
	public function acceptType($type)
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($type)->isModelCompatible($this->m_type);
	}

	/**
	 * Indicates whether the document property accepts all types of document.
	 *
	 * @return boolean
	 */
	public function acceptAllTypes()
	{
		return ($this->m_type == '*' ||
				$this->m_type == 'modules_generic/Document');
	}

	/**
	 * Returns the name of the field that represents this property into the
	 * database table.
	 *
	 * @return string
	 */
	public function getDbMapping()
	{
		return $this->m_dbMapping;
	}

	/**
	 * Returns the database table name.
	 *
	 * @return string
	 */
	public function getDbTable()
	{
		return $this->m_dbTable;
	}

	public function isPrimaryKey()
	{
		return $this->m_primaryKey;
	}

	public function isCascadeDelete()
	{
		return $this->m_cascadeDelete;
	}

	/**
	 * Indicates whether the property is a virtual tree node or not.
	 *
	 * @return boolean
	 */
	public function isTreeNode()
	{
		return $this->m_treeNode;
	}

	/**
	 * Returns the "min-occurs" value.
	 *
	 * @return integer
	 */
	public function getMinOccurs()
	{
		return $this->m_minOccurs;
	}

	/**
	 * Returns the "min-occurs" value.
	 *
	 * @return integer
	 */
	public function getMaxOccurs()
	{
		return $this->m_maxOccurs;
	}

	/**
	 * Returns the "from-list" value.
	 * @return string | null
	 */
	public function getFromList()
	{
		return $this->m_fromList;
	}
	
	/**
	 * @param Integer $value
	 * @return PropertyInfo
	 */
	public function setMinOccurs($value)
	{
		$this->m_minOccurs = intval($value);
		return $this;
	}

	/**
	 * @param Integer $value
	 * @return PropertyInfo
	 */
	public function setMaxOccurs($value)
	{
		$this->m_maxOccurs = intval($value);
		$this->m_isArray = $value > 1 || $value == -1;
		return $this;
	}
	
	/**
	 * @return Boolean
	 */
	public function isRequired()
	{
		return $this->m_minOccurs > 0;
	}
	
	/**
	 * Indicates whether the property is a document or not.
	 *
	 * @return boolean
	 */
	public function isDocument()
	{
		return $this->m_isDocument;
	}
	
	/**
	 * Shortcut to get document model if the property is a document property 
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	public function getDocumentModel()
	{
		if (!$this->m_isDocument)
		{
			throw new Exception("Invalid call to ".__METHOD__.": ".$this->m_name." is not a document property");
		}
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($this->m_type);
	}

	/**
	 * Indicates whether the property is multi-valued or not.
	 *
	 * @return boolean
	 */
	public function isArray()
	{
		return $this->m_isArray;
	}

	/**
	 * Indicates whether the property is unique or not.
	 *
	 * @return boolean
	 */
	public function isUnique()
	{
		return !$this->m_isArray;
	}

	/* Information de présentation */

	/**
	 * @return String
	 */
	public function getDefaultValue()
	{
		return $this->m_defaultValue;
	}

	/**
	 * @param String $value
	 * @return PropertyInfo
	 */
	public function setDefaultValue($value)
	{
		$this->m_defaultValue = $value;
		return $this;
	}

	/**
	 * Returns the constraints string defined for the property.
	 *
	 * @return string
	 */
	public function getConstraints()
	{
		return $this->m_constraints;
	}

	/**
	 * @param String $value
	 * @return PropertyInfo
	 */
	public function setConstraints($constraints)
	{
		$this->m_constraints = $constraints;
		return $this;
	}

	/**
	 * Indicates whether the property is localized or not.
	 *
	 * @return boolean
	 */
	public function isLocalized()
	{
		return $this->m_isLocalized;
	}
	
	/**
	 * is the property declared as indexed ?
	 * @return Boolean
	 */
	public function isIndexed()
	{
		return $this->m_isIndexed;
	}
	
	/**
	 * does the property have a dedicated index ?
	 * @return Boolean
	 */
	public function hasSpecificIndex()
	{
		return $this->m_hasSpecificIndex;
	}

	/**
	 * @param Boolean $bool
	 * @return PropertyInfo
	 */
	public function setLocalized($bool)
	{
		$this->m_isLocalized = $bool ? true : false;
		return $this;
	}

	/**
	 * Indicates whether the property is a string or not.
	 *
	 * @return boolean
	 */
	public function isString()
	{
		return f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING == $this->m_type;
	}

	/**
	 * Indicates whether the property is a long string or not.
	 *
	 * @return boolean
	 */
	public function isLob()
	{
		switch ($this->m_type)
		{
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_LOB:
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_LONGSTRING:
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT:
				return true;
			default:
				return false;
		}
	}

	/**
	 * @return Integer or -1
	 */
	public function getMaxSize()
	{
		if ($this->m_constraints !== null && $this->isString())
		{
			$match = array();
			preg_match("/maxSize:([0-9]+)/", $this->m_constraints, $match);
			if (isset($match[1]))
			{
				return intval($match[1]);
			}
		}
		return -1;
	}
}
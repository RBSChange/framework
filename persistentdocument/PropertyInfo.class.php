<?php
/**
 * @package framework.persistentdocument
 */
class PropertyInfo
{
	private $name;
	private $type = f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING;
	private $documentType = null;
	private $required = false;
	private $minOccurs = 0;
	private $maxOccurs = 1;
	private $dbMapping;
	private $dbTable;
	private $cascadeDelete = false;
	private $treeNode = false;
	private $isDocument = false;
	private $defaultValue;
	private $constraintArray;
	private $localized = false;
	private $indexed = 'none'; //none, property, description
	private $fromList;

	/**
	 * @param string $name
	 * @param string $type
	 */
	function __construct($name, $type = null)
	{
		$this->name = $name;
		if ($type != null)
		{
			$this->setType($type);
		}
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * @return string|NULL
	 */
	public function getDocumentType()
	{
		return $this->documentType;
	}
	
	/**
	 * @return boolean
	 */
	public function getTreeNode()
	{
		return $this->treeNode;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}
	
	/**
	 * Returns the name of the field that represents this property into the
	 * database table.
	 *
	 * @return string
	 */
	public function getDbMapping()
	{
		return $this->dbMapping;
	}

	/**
	 * Returns the database table name.
	 *
	 * @return string
	 */
	public function getDbTable()
	{
		return $this->dbTable;
	}
	
	/**
	 * @return integer
	 */
	public function getMinOccurs()
	{
		return max($this->minOccurs, $this->isRequired() ? 1 : 0);
	}

	/**
	 * @return integer
	 */
	public function getMaxOccurs()
	{
		return $this->maxOccurs;
	}

	/**
	 * @return string | null
	 */
	public function getFromList()
	{
		return $this->fromList;
	}	
	
	/**
	 * @return boolean
	 */
	public function getCascadeDelete()
	{
		return $this->cascadeDelete;
	}

	/**
	 * @return boolean
	 */
	public function getLocalized()
	{
		return $this->localized;
	}
	
	

	/**
	 * @return string [none], property, description
	 */
	public function getIndexed()
	{
		return $this->indexed;
	}
	
	/**
	 * @return boolean
	 */
	public function isIndexed()
	{
		return $this->indexed != 'none';
	}

	/**
	 * @return f_persistentdocument_PersistentDocumentModel|NULL
	 */
	public function getPersistentModel()
	{
		if ($this->documentType)
		{
			return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($this->documentType);
		}
		return null;
	}
		

	/**
	 * Returns the type of subdocuments with the slash replaced by an underscore
	 * for use on the backoffice side.
	 *
	 * @return string|NULL
	 */
	public function getTypeForBackofficeWidgets()
	{
		if ($this->documentType)
		{
			return f_persistentdocument_PersistentDocumentModel::convertModelNameToBackoffice($this->documentType);
		}
		return null;
	}

	/**
	 * Indicates whether the document property accepts documents of type $type.
	 *
	 * @return boolean
	 */
	public function acceptType($type)
	{
		if ($this->documentType)
		{
			return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($type)->isModelCompatible($this->documentType);
		}
		return false;
	}

	/**
	 * Indicates whether the document property accepts all types of document.
	 *
	 * @return boolean
	 */
	public function acceptAllTypes()
	{
		if ($this->isDocument())
		{
			return $this->documentType === f_persistentdocument_PersistentDocumentModel::BASE_MODEL;
		}
		return false;
	}
	
	/**
	 * Indicates whether the property is a string or not.
	 *
	 * @return boolean
	 */
	public function isString()
	{
		return $this->type === f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING;
	}

	/**
	 * Indicates whether the property is a long string or not.
	 *
	 * @return boolean
	 */
	public function isLob()
	{
		switch ($this->type)
		{
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_LOB:
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_BBCODE:
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_JSON:
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_OBJECT:
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_LONGSTRING:
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT:
				return true;
			default:
				return false;
		}
	}
	
	/**
	 * Indicates whether the property is a number.
	 *
	 * @return boolean
	 */
	public function isNumeric()
	{
		switch ($this->type)
		{
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER:
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DECIMAL:
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOUBLE:
				return true;
			default:
				return false;
		}
	}
	
	/**
	 * Indicates whether the property is a document or not.
	 *
	 * @return boolean
	 */
	public function isDocument()
	{
		return $this->isDocument;
	}

	/**
	 * @param Integer $value
	 * @return PropertyInfo
	 */
	public function setMinOccurs($value)
	{
		$this->minOccurs = intval($value);
		return $this;
	}

	/**
	 * @param Integer $value
	 * @return PropertyInfo
	 */
	public function setMaxOccurs($value)
	{
		$this->maxOccurs = intval($value);
		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function isRequired()
	{
		return $this->getRequired();
	}
	
	/**
	 * @return boolean
	 */
	public function getRequired()
	{
		return $this->required;
	}
	
	/**
	 * @param boolean $value
	 * @return PropertyInfo
	 */
	public function setRequired($value)
	{
		$this->required = ($value == true);
		return $this;
	}
	
	/**
	 * Indicates whether the property is multi-valued or not.
	 *
	 * @return boolean
	 */
	public function isArray()
	{
		return $this->maxOccurs != 1;
	}

	/**
	 * Indicates whether the property is unique or not.
	 *
	 * @return boolean
	 */
	public function isUnique()
	{
		return $this->maxOccurs == 1;
	}

	/* Information de présentation */

	/**
	 * @return String
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @param String $value
	 * @return PropertyInfo
	 */
	public function setDefaultValue($value)
	{
		$this->defaultValue = $value;
		return $this;
	}
	
	/**	
	 * Returns the constraints defined for the property.
	 *
	 * @return array
	 */
	public function getConstraintArray()
	{
		return $this->constraintArray;
	}

	/**
	 * @return Integer or -1
	 */
	public function getMaxSize()
	{
		if ($this->isString() && is_array($this->constraintArray) && isset($this->constraintArray['maxSize']))
		{
			return intval($this->constraintArray['maxSize']['parameter']);
		}
		return -1;
	}
	
	/**
	 * @param string $name
	 * @return PropertyInfo
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param string $type
	 * @return PropertyInfo
	 */
	public function setType($type)
	{
		$this->type = $type;
		$this->isDocument = ($this->type === f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENT || 
			$this->type === f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENTARRAY);
		
		if ($this->maxOccurs === 1 && $this->type === f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENTARRAY)
		{
			$this->setMaxOccurs(-1);
		}
		if ($this->documentType === null && ($this->isDocument || $this->type === f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENTID))
		{
			$this->setDocumentType(f_persistentdocument_PersistentDocumentModel::BASE_MODEL);
		}	
		return $this;
	}
	
	/**
	 * @param string $documentType
	 * @return PropertyInfo
	 */
	public function setDocumentType($documentType)
	{
		$this->documentType = $documentType;
		return $this;
	}

	/**
	 * @param string $dbMapping
	 * @return PropertyInfo
	 */
	public function setDbMapping($dbMapping)
	{
		$this->dbMapping = $dbMapping;
		return $this;
	}

	/**
	 * @param string $dbTable
	 * @return PropertyInfo
	 */
	public function setDbTable($dbTable)
	{
		$this->dbTable = $dbTable;
		return $this;
	}

	/**
	 * @param boolean $cascadeDelete
	 * @return PropertyInfo
	 */
	public function setCascadeDelete($cascadeDelete)
	{
		$this->cascadeDelete = $cascadeDelete;
		return $this;
	}

	/**
	 * @param string $indexed
	 * @return PropertyInfo
	 */
	public function setIndexed($indexed)
	{
		$this->indexed = $indexed;
		return $this;
	}

	/**
	 * @param string $fromList
	 * @return PropertyInfo
	 */
	public function setFromList($fromList)
	{
		$this->fromList = $fromList;
		return $this;
	}

	/**
	 * @param mixed $value
	 * @return PropertyInfo
	 */
	public function setConstraints($constraints)
	{
		if ($constraints === null || is_array($constraints))
		{
			$this->constraintArray = $constraints;
		}
		elseif (is_string($constraints))
		{
			$cp = new validation_ContraintsParser();
			$defs = $cp->getConstraintArrayFromDefinition($constraints);
			foreach ($defs as $name => $parameter) 
			{
				$params = array('parameter' => $parameter);
				if ($this->constraintArray === null) {$this->constraintArray = array();}
				if ($name{0} === '!')
				{
					$name = substr($name, 1);
					$params['reversed'] = true;
				}
				$this->constraintArray[$name] = $params;
			}
		}
		return $this;
	}
	
	/**
	 * @param boolean $bool
	 * @return PropertyInfo
	 */
	public function setLocalized($bool)
	{
		$this->localized = $bool ? true : false;
		return $this;
	}
	
	/**
	 * @param mixed $treeNode
	 * @return PropertyInfo
	 */
	public function setTreeNode($treeNode)
	{
		$this->treeNode = $treeNode;
		return $this;
	}
	
		
	/**
	 * @deprecated with no replacement
	 */
	public function isPrimaryKey()
	{
		return false;
	}
	
	/**
	 * @deprecated with no replacement
	 */
	public function hasSpecificIndex()
	{
		return false;
	}
	
	/**
	 * @deprecated use getTreeNode
	 */
	public function isTreeNode()
	{
		return $this->treeNode;
	}
	
	/**
	 * @deprecated use getCascadeDelete
	 */
	public function isCascadeDelete()
	{
		return $this->getCascadeDelete();
	}
	
	/**
	 * @deprecated use getLocalized
	 */
	public function isLocalized()
	{
		return $this->getLocalized();
	}
	
	/**
	 * @deprecated use getPersistentModel
	 */
	public function getDocumentModel()
	{
		return $this->getPersistentModel();
	}
	
	/**
	 * @deprecated
	 */
	public function getConstraints()
	{
		if (is_array($this->constraintArray))
		{
			$const = array();
			if ($this->isRequired())
			{
				$const[] = 'blank:false';
			}
			foreach ($this->constraintArray as $name => $params) 
			{
				if (isset($params['reversed'])) 
				{
					$name = '!' . $name;
					unset($params['reversed']);
				}
				if (isset($params['parameter']))
				{
					$const[] = $name . ':' . $params['parameter'];
				}
				elseif (count($params))
				{
					$const[] = $name . ':' . f_util_ArrayUtils::firstElement($params);
				}
				else
				{
					$const[] = $name . ':true';
				}
			}
			return count($const) ? implode(';', $const) : null;
		}
		return $this->constraintArray;
	}
}
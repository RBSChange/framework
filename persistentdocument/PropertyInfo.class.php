<?php
/**
 * @package framework.persistentdocument
 */
class PropertyInfo
{
	private $name;
	private $type = f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING;
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
		return $this->minOccurs;
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
	 * @return f_persistentdocument_PersistentDocumentModel || null
	 */
	public function getPersistentModel()
	{
		if ($this->isDocument)
		{
			return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($this->type);
		}
		return null;
	}
		

	/**
	 * Returns the type of subdocuments with the slash replaced by an underscore
	 * for use on the backoffice side.
	 *
	 * @return string
	 */
	public function getTypeForBackofficeWidgets()
	{
		return f_persistentdocument_PersistentDocumentModel::convertModelNameToBackoffice($this->type);
	}

	/**
	 * Indicates whether the document property accepts documents of type $type.
	 *
	 * @return boolean
	 */
	public function acceptType($type)
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($type)->isModelCompatible($this->type);
	}

	/**
	 * Indicates whether the document property accepts all types of document.
	 *
	 * @return boolean
	 */
	public function acceptAllTypes()
	{
		return $this->type === 'modules_generic/Document';
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
	 * @return Boolean
	 */
	public function isRequired()
	{
		return $this->minOccurs > 0;
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
	 * Returns the constraints string defined for the property.
	 *
	 * @return string
	 */
	public function getConstraints()
	{
		if (is_array($this->constraintArray))
		{
			$const = array();
			foreach ($this->constraintArray as $name => $params) 
			{
				if (isset($params['reversed'])) {$name = '!' . $name;}
				$const[] = $name . ':' . (isset($params['parameter']) ? $params['parameter'] : 'true');
			}
			return count($const) ? implode(';', $const) : null;
		}
		return $this->constraintArray;
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
		$this->isDocument = (strpos($this->type, 'modules_') === 0);
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
		return $this->cascadeDelete;
	}
	
	/**
	 * @deprecated use getLocalized
	 */
	public function isLocalized()
	{
		return $this->localized;
	}
	
	/**
	 * @deprecated use getPersistentModel
	 */
	public function getDocumentModel()
	{
		if (!$this->isDocument())
		{
			throw new Exception("Invalid call to ".__METHOD__.": ".$this->name." is not a document property");
		}
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($this->type);
	}
}
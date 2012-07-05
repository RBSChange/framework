<?php
/**
 * @package framework.builder.generator
 */
class generator_PersistentProperty
{
	const PROPERTY = 1;
	const SERIALISED_PROPERTY = 2;
	
	protected static $TYPES = array('String', 'Boolean', 'Integer', 'Double', 'Decimal',
				'DateTime', 'LongString', 'XHTMLFragment', 'Lob', 'BBCode', 'JSON', 'Object',
				'DocumentId', 'Document', 'DocumentArray');
	
	/**
	 * @var generator_PersistentModel
	 */
	private $model;
	
	private $indexed;

	private $name;
	private $type;
	private $documentType;
	
	/**
	 * @var generator_PersistentModel
	 */
	private $typeModel; // Used for inverse properties sorting and set only for them.
	
	private $required;
	private $minOccurs;
	private $maxOccurs;
	private $dbName;
	private $dbMapping;
	private $dbSize;

	private $fromList;
	private $cascadeDelete;

	private $defaultValue;
	private $inverse;

	private $constraintArray;
	
	private $treeNode;
	private $treeNodeInverse;
	private $localized;

	private $preserveOldValue;

	private $relationName;
	private $tableName;
	
	private $modelPart = self::PROPERTY;
	
	
	/**
	 * @var generator_PersistentProperty
	 */
	private $parentProperty;
	
	private $isSerializedProperty = false;

	/**
	 * @param generator_PersistentModel $model
	 */
	public function __construct($model)
	{
		$this->setModel($model);
	}
	
	/**
	 * @param generator_PersistentModel $model
	 */
	public function setModelPart($modelPart)
	{
		$this->modelPart = ($modelPart === self::SERIALISED_PROPERTY) ? $modelPart : self::SERIALISED_PROPERTY;
	}
	
	/**
	 * @param generator_PersistentModel $model
	 */
	public function setModel($model)
	{
		$this->model = $model;
	}
	
	public function setSerializedProperty($isSerialized = true)
	{
		$this->isSerializedProperty = $isSerialized;
	}

	/**
	 * @param DOMElement $xmlElement
	 */
	public function initialize($xmlElement)
	{
		foreach($xmlElement->attributes as $attribute)
		{
			$name = $attribute->nodeName;
			$value = $attribute->nodeValue;

			switch ($name)
			{
				case "indexed":
					if ($value == 'description' || $value == 'property')
					{
						$this->indexed = $value;
					}
					else
					{
						$this->indexed = 'none';
					}
					break;
				case "name":
					$this->name = $value;
					break;
				case "type":
					if (!in_array($value, self::$TYPES))
					{
						generator_PersistentModel::addMessage("Invalid property Type ". $this->model->getName() . " : $name => $value ");
						$value = 'String';
					}
					$this->type = $value;
					break;
				case "document-type":
					$this->documentType = $value;
					break;
				case "from-list":
					$this->fromList = $value;
					break;
				case "cascade-delete":
					$this->cascadeDelete = generator_PersistentModel::getBoolean($value);
					break;
				case "default-value":
					$this->defaultValue = $value;
					break;
				case "required":
					$this->required = generator_PersistentModel::getBoolean($value);
					break;
				case "min-occurs":
					$this->minOccurs = intval($value);
					break;
				case "max-occurs":
					$this->maxOccurs = intval($value);
					break;
				case "db-mapping":
					$this->dbMapping = $value;
					break;
				case "db-size":
					$this->dbSize = $value;
					break;
				case "tree-node":
					//$this->treeNode = generator_PersistentModel::getBoolean($value);
					$this->treeNode = $value;
					break;
				case "localized":
					$this->localized = generator_PersistentModel::getBoolean($value);
					break;
				case "inverse":
					$this->inverse = generator_PersistentModel::getBoolean($value);
					break;
				case "preserve-old-value":
					$this->preserveOldValue = generator_PersistentModel::getBoolean($value);
					break;
				default:
					generator_PersistentModel::addMessage("Obsolete property attribute ". $this->model->getName() . " : $name => $value ");
					break;
			}
		}

		// When the "inverse" property is set to "true", the "tree-node" property
		// may have one of the following values:
		// - both    : both properties will be virtual tree nodes
		// - direct  : only this property will be a virtual tree node
		// - inverse : only the inverse property will be a virtual node
		// - none    : no virtual tree node
		// For compatibility purpose, 'true' equals to 'both' and 'false' equals to 'none'.
		if ($this->inverse)
		{
			switch ($this->treeNode)
			{
				case 'true' : // deprecated: use 'both'
				case 'both' :
					$this->treeNode = true;
					$this->treeNodeInverse = true;
					break;

				case 'direct' :
					$this->treeNode = true;
					$this->treeNodeInverse = false;
					break;

				case 'inverse' :
					$this->treeNode = false;
					$this->treeNodeInverse = true;
					break;

				case 'false' : // deprecated: use 'none'
				case 'none' :
				default :
					$this->treeNode = false;
					$this->treeNodeInverse = false;
					break;
			}
		}
		// When "inverse" is false, "tree-node" must be a boolean.
		else
		{
			$this->treeNode = generator_PersistentModel::getBoolean($this->treeNode);
			$this->treeNodeInverse = false;
		}

		foreach ($xmlElement->childNodes as $node)
		{
			/* @var $node DOMElement */
			if ($node->nodeName == 'constraint')
			{
				$params = array();
				$name = null;
				foreach ($node->attributes as $attr) 
				{
					if ($attr->name === 'name')
					{
						$name = $attr->value;
					}
					else
					{
						$v = $attr->value;
						if ($v === 'true') {$v = true;} elseif ($v === 'false') {$v = false;}
						$params[$attr->name] = $v;
					}
				}
				if ($name)
				{
					if ($this->constraintArray === null) {$this->constraintArray = array();}
					$this->constraintArray[$name] = $params;
				}
			}
			elseif ($node->nodeType == XML_ELEMENT_NODE)
			{
				generator_PersistentModel::addMessage("Invalid property children node ". $this->model->getName() . " : " . $this->getName() . ' -> ' . $node->nodeName);
			}
		}
	}

	/**
	 * @param generator_PersistentModel $document
	 */
	public static function generateIdProperty($model)
	{
		$property = new generator_PersistentProperty($model);
		$property->cascadeDelete = false;
		$property->name = 'id';
		$property->dbMapping = 'document_id';
		$property->required = true;
		$property->type = f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER;
		$property->localized = false;
		return $property;
	}

	/**
	 * @param generator_PersistentModel $document
	 */
	public static function generateModelProperty($model)
	{
		$property = new generator_PersistentProperty($model);
		$property->cascadeDelete = false;
		$property->name = 'model';
		$property->dbMapping = 'document_model';
		$property->required = true;
		$property->type = f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING;
		$property->localized = false;
		$property->indexed = 'none';
		return $property;
	}

	/**
	 * @param generator_PersistentModel $document
	 */
	public static function generateCorrectionIdProperty($model)
	{
		$property = new generator_PersistentProperty($model);
		$property->cascadeDelete = false;
		$property->name = 'correctionid';
		$property->dbMapping = 'document_correctionid';
		$property->required = false;
		$property->type = f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER;
		$property->localized = false;
		return $property;
	}

	/**
	 * @param generator_PersistentModel $document
	 */
	public static function generateCorrectionOfIdProperty($model)
	{
		$property = new generator_PersistentProperty($model);
		$property->cascadeDelete = false;
		$property->name = 'correctionofid';
		$property->dbMapping = 'document_correctionofid';
		$property->required = false;
		$property->type = f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER;
		$property->localized = false;
		return $property;
	}
	
	/**
	 * @param generator_PersistentModel $document
	 */
	public static function generateS18sProperty($model)
	{
		$property = new generator_PersistentProperty($model);
		$property->cascadeDelete = false;
		$property->name = 's18s';
		$property->dbMapping = 'document_s18s';
		$property->required = false;
		$property->type = f_persistentdocument_PersistentDocument::PROPERTYTYPE_LOB;
		return $property;
	}	

	/**
	 * @param generator_PersistentProperty $property
	 */
	public static function generateInverseProperty($property)
	{
		$model = generator_PersistentModel::getModelByName($property->getDocumentTypeRecursive());
		$invertProperty = new generator_PersistentProperty($model);
		$tmp = explode('/', $property->model->getName());
		$invertProperty->name = $tmp[1];
		$invertProperty->type = $property->type;
		$invertProperty->documentType = $property->model->getName();
		$invertProperty->typeModel = $property->model;
		$invertProperty->required = $property->required;
		$invertProperty->minOccurs = $property->minOccurs;
		$invertProperty->maxOccurs = $property->maxOccurs;
		$invertProperty->dbMapping = $property->getDbName();
		$invertProperty->relationName = $property->name;
		$invertProperty->treeNode = $property->treeNodeInverse;
		$invertProperty->tableName =  $property->model->getTableName();
		return $invertProperty;
	}

	/**
	 * Used for inverse properties sorting.
	 * @return generator_PersistentModel
	 */
	public function getTypeModel()
	{
		return $this->typeModel;
	}
		
	/**
	 * @return Boolean
	 */
	public function isDocument()
	{
		return $this->type === f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENT || 
			$this->type === f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENTARRAY;
	}

	/**
	 * @return Boolean
	 */
	public function isLocalized()
	{
		return (!is_null($this->localized) && $this->localized);
	}

	/**
	 * @param boolean $localized
	 * @return void
	 */
	public function setLocalized($localized = true)
	{
		$this->localized = $localized;
	}

	/**
	 * @return Boolean
	 */
	public function isInverse()
	{
		return !is_null($this->inverse) && $this->inverse;
	}

	/**
	 * @return String
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getIndexed()
	{
		if (is_null($this->indexed))
		{
			if (!is_null($this->parentProperty))
			{
				return $this->parentProperty->getIndexed();
			}
			switch ($this->type) {
				case null:
				case 'String':
				case 'LongString':
				case 'XHTMLFragment':
					return 'property';
				default:
					return 'none';
			}
		}		
		return $this->indexed;
	}

	/**
	 * @param generator_PersistentProperty $property
	 */
	public function mergeGeneric($property)
	{
		$this->type = $property->type;
		$this->documentType = $property->documentType;
		$this->dbMapping = $property->dbMapping;
		$this->maxOccurs = $property->maxOccurs;
		if (is_null($this->dbSize)) {$this->dbSize = $property->dbSize;}
		if (is_null($this->required)) {$this->required = $property->required;}
		if (is_null($this->minOccurs)) {$this->minOccurs = $property->minOccurs;}
		if (is_null($this->indexed)) {$this->indexed = $property->indexed;}
	}

	/**
	 * @param generator_PersistentProperty $parentProperty
	 */
	public function setParentProperty($parentProperty)
	{
		$this->parentProperty = $parentProperty;

		// Override not modifiable attributes.
		$this->localized = $parentProperty->isLocalized();
		if (!$parentProperty->isDocument() || is_null($this->type))
		{
			$this->type = $parentProperty->type;
		}
		$this->cascadeDelete = $parentProperty->cascadeDelete;
		$this->dbMapping = $parentProperty->dbMapping;
		$this->treeNode = $parentProperty->treeNode;
		$this->inverse = $parentProperty->inverse;
		
		//defaultValue -> seulement si redefini
		//preserveOldValue
	}
	
	/**
	 * @return Boolean
	 */
	public function isOverride()
	{
		return $this->parentProperty !== null;
	}
	
	/**
	 * @return generator_PersistentProperty
	 */
	public function getParentProperty()
	{
		return $this->parentProperty;
	}	

	public function applyDefaultConstraints()
	{		
		if ($this->type == f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING)
		{
			if (intval($this->dbSize) <= 0 || intval($this->dbSize) > 255) 
			{
				$params = array('max' => '255');
			}
			else
			{
				$params = array('max' => $this->dbSize);
			}
			
			if ($this->constraintArray === null) {$this->constraintArray = array();}
			if (!isset($this->constraintArray['maxSize']))
			{
				$this->constraintArray['maxSize'] = $params;
			} 
		}
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return $this->type;
	}
	
	/**
	 * @return String
	 */
	public function getDocumentType()
	{
		return $this->documentType;
	}
	
	/**
	 * @return String
	 */
	public function getDocumentTypeRecursive()
	{
		if ($this->documentType !== null)
		{
			return $this->documentType;
		}
		elseif ($this->parentProperty)
		{
			return $this->parentProperty->getDocumentTypeRecursive();
		}
		throw new Exception('no document type defined');
	}

	/**
	 * @return Integer
	 */
	public function getMinOccurs()
	{
		if ($this->minOccurs === null && !is_null($this->parentProperty))
		{
			return $this->parentProperty->getMinOccurs();
		}		
		return $this->minOccurs === null ? 0 : $this->minOccurs;
	}

	/**
	 * @return Integer
	 */
	public function getMaxOccurs()
	{
		if ($this->maxOccurs === null && !is_null($this->parentProperty))
		{
			return $this->parentProperty->getMaxOccurs();
		}	
		return $this->maxOccurs === null ? 1 : $this->maxOccurs;
	}

	/**
	 * @return String
	 */
	public function getDbName()
	{
		if ($this->dbName === null)
		{
			$this->dbName = f_persistentdocument_PersistentProvider::getInstance()
				->getSchemaManager()->generateSQLModelFieldName($this->name, $this->dbMapping);
		}
		return $this->dbName;
	}

	/**
	 * @return String
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}
	
	/**
	 * @return boolean
	 */
	public function hasDefinedConstraints()
	{
		return is_array($this->constraintArray);
	}

	/**
	 * @return boolean
	 */
	public function hasAncestorsConstraints()
	{
		$pProp = $this->parentProperty;
		while ($pProp)
		{
			if (is_array($pProp->constraintArray)) {return true;}
			$pProp = $pProp->parentProperty;
		}
		return false;
	}
	
	/**
	 * @return array
	 */
	public function getConstraintArray()
	{
		$parentConstraint = $this->parentProperty ? $this->parentProperty->getConstraintArray() : null;
		if (is_array($this->constraintArray) && is_array($parentConstraint))
		{
			return array_merge($parentConstraint, $this->constraintArray);
		}
		return is_array($this->constraintArray) ? $this->constraintArray : $parentConstraint;
	}	
	
	/**
	 * @return string
	 */
	public function buildPhpConstraintArray()
	{
		$cs = $this->getConstraintArray();
		if (is_array($cs) && count($cs))
		{
			$php = array();
			foreach ($cs as $name => $params) 
			{
				$c = var_export($name, true) . " => array(";
				foreach ($params as $pn => $pv)
				{
					$c .= var_export($pn, true) . " => " . var_export($pv, true) . ', ';
				} 
				$php[] = $c. ')';
			}
			
			return 'array(' . implode(', ', $php) . ')';
		}
		return 'null';
	}	
	
	/**
	 * @return Boolean
	 */
	public function isCascadeDelete()
	{
		return is_null($this->cascadeDelete) ? false : $this->cascadeDelete;
	}

	/**
	 * @return Boolean
	 */
	public function hasCascadeDelete()
	{
		return is_null($this->cascadeDelete) ? false : $this->cascadeDelete;
	}


	/**
	 * @return Boolean
	 */
	public function isTreeNode()
	{
		return is_null($this->treeNode) ? false : $this->treeNode;
	}

	/**
	 * @return Boolean
	 */
	public function isArray()
	{
		return ($this->type === f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENTARRAY);
	}
	
	/**
	 * @return Boolean
	 */
	public function isRequired()
	{
		if ($this->required === null && !is_null($this->parentProperty))
		{
			return $this->parentProperty->isRequired();
		}
		return $this->required === null ? false : $this->required;
	}

	/**
	 * @return String
	 */
	public function getFromList()
	{
		if ($this->fromList === null && !is_null($this->parentProperty))
		{
			return $this->parentProperty->getFromList();
		}
		return $this->fromList;
	}

	/**
	 * @return String
	 */
	public function getTableName()
	{
		return $this->tableName;
	}


	public function getPreserveOldValue()
	{
		if ($this->preserveOldValue === null && !is_null($this->parentProperty))
		{
			return $this->parentProperty->getPreserveOldValue();
		}
		return is_null($this->preserveOldValue) ? false : $this->preserveOldValue;
	}


	/**
	 * @return String
	 */
	public function getPhpDefaultValue()
	{
		if (is_null($this->defaultValue) || $this->isDocument())
		{
			return null;
		}
		if (!$this->isLocalized() || $this->name == 'label')
		{
			if ($this->isSerializedProperty)
			{
				$ret = '		$this->setS18sProperty(\''. $this->name . '\', ';
			}
			else
			{
				$ret = '		$this->set' . ucfirst($this->name) . 'Internal(';
			}
		}
		else
		{
			return null;
		}

		$ret .= $this->getPhpValue($this->defaultValue);

		return $ret . ');';
	}

	/**
	 * @return String
	 */
	public function getPhpI18nDefaultValue()
	{
		if (is_null($this->defaultValue))
		{
			return null;
		}
		
		if ($this->isSerializedProperty)
		{
			$ret = '		$this->setS18sProperty(\''. $this->name . '\', ';
		}
		else
		{
			$ret = '		$this->set' . ucfirst($this->name) . '(';
		}
		$ret .= $this->getPhpValue($this->defaultValue);

		return $ret . ');';
	}
	/**
	 * @param String $value
	 * @return String
	 */
	private function getPhpValue($value)
	{
		switch ($this->getType())
		{
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN :
				return generator_PersistentModel::escapeBoolean(generator_PersistentModel::getBoolean($value));
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOUBLE :
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER :
				return $value;
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DATETIME :
				if (strtolower($value) == 'now')
				{
					return 'date("Y-m-d H:i:s")';
				}
				else if (strtolower($value) == 'date')
				{
					return 'date("Y-m-d 00:00:00")';
				}
				else
				{
					return generator_PersistentModel::escapeString($value);
				}
			default:
				return generator_PersistentModel::escapeString($value);
				break;
		}
	}

	/**
	 * @return String
	 */
	public function getPhpName()
	{
		return ucfirst($this->name);
	}

	public function phpPropertyValidationMethod()
	{
		$phpScript = array();
		$name = $this->name;
		if (in_array($name, array('id', 'model', 'lang'))) {return;}
		$constraintArray = $this->getConstraintArray();
		$required = $this->isRequired();
		if (!is_array($constraintArray) && !$required && $this->getMaxOccurs() <= 1)
		{
			return;
		}
		
		$uName = ucfirst($name);	
		$phpScript[] = '	protected function is' . $uName .'Valid()';
		$phpScript[] = '	{';
		if ($this->isDocument())
		{
			$phpScript[] = '		$this->checkLoaded' . $uName .'();';
			if ($this->isArray())
			{
				if (is_array($constraintArray))
				{
					throw new Exception("Invalid constraints for document property " . $this->model->getName() . '/' . $name);
				}
				
				$if = array();
				if ($required) {$if[] = max($this->getMinOccurs(), 1) .' > $count';}
				if ($this->getMaxOccurs() > 1 ) {$if[] =  $this->getMaxOccurs() .' < $count';}			
				$phpScript[] = '		$count = $this->get'.$uName .'Count();';
				$phpScript[] = '		if ('. implode(' || ', $if) .') {';	
				$phpScript[] = '			$args = array(\'minOccurs\' => '. $this->getMinOccurs() .', \'maxOccurs\' => '. $this->getMaxOccurs() .', \'count\' => $count);';
				$phpScript[] = '			$this->addPropertyErrors(\''.$name.'\', LocaleService::getInstance()->trans(\'f.constraints.notbetweendocumentarray\', array(\'ucf\'), array($args)));';
				$phpScript[] = '			return false;';
				$phpScript[] = '		}';
			}
			elseif ($required)
			{
				$phpScript[] = '		if ($this->get'.$uName .'() === null) {';	
				$phpScript[] = '			$this->addPropertyErrors(\''.$name.'\', LocaleService::getInstance()->trans(\'f.constraints.isempty\', array(\'ucf\')));';
				$phpScript[] = '			return false;';
				$phpScript[] = '		}';
			}
			
			if (is_array($constraintArray))
			{	
				$phpScript[] = '		$constraints = $this->getPersistentModel()->'.$this->generatePhpPropertyModelGetter().'->getConstraintArray();';		
				$phpScript[] = '		foreach ($constraints as $name => $params) {';
				$phpScript[] = '			$params += array(\'documentId\' => $this->getId());';	
				$phpScript[] = '			$c = change_Constraints::getByName($name, $params);';		
				$phpScript[] = '			if (!$c->isValid($this->get'.$uName .'())) {';		
				$phpScript[] = '				$this->addPropertyErrors(\''.$name.'\', change_Constraints::formatMessages($c));';		
				$phpScript[] = '				return false;';		
				$phpScript[] = '			}';		
				$phpScript[] = '		}';		
			}
			$phpScript[] = '		return true;';
		}
		else if (is_array($this->constraintArray) || $required)
		{
			$phpScript[] = $this->generatePhpValidators($this->constraintArray);
		}
		else
		{
			$phpScript[] = '		return true;';
		}
		$phpScript[] = '	}';
		$phpScript[] = '';
		return join(PHP_EOL, $phpScript);
	}
	
	private function generatePhpPropertyModelGetter()
	{
		if ($this->modelPart === self::PROPERTY)
		{
			return 'getProperty("'.$this->name.'")';
		}
		return 'getSerializedProperty("'.$this->name.'")';
	}

	private function generatePhpValidators($constraintsArray)
	{
		$required = $this->isRequired();
		$name = $this->getName();
		$uName = ucfirst($name);
		$php = array();
		$php[] = '		$value = $this->get'.$uName.'();';
		$php[] = '		$prop = $this->getPersistentModel()->'.$this->generatePhpPropertyModelGetter().';';
		$php[] = '		if ($value === null || $value === \'\') {';	
		$php[] = '			if (!$prop->isRequired()) {return true;}';			
		$php[] = '			$this->addPropertyErrors(\''.$name.'\', LocaleService::getInstance()->trans(\'f.constraints.isempty\', array(\'ucf\')));';
		$php[] = '			return false;';
		$php[] = '		}';

		if (is_array($constraintsArray))
		{
			$php[] = '		foreach ($prop->getConstraintArray() as $name => $params) {';
			$php[] = '			$params += array(\'documentId\' => $this->getId());';		
			$php[] = '			$c = change_Constraints::getByName($name, $params);';		
			$php[] = '			if (!$c->isValid($value)) {';		
			$php[] = '				$this->addPropertyErrors(\''.$name.'\', change_Constraints::formatMessages($c));';
			$php[] = '				return false;';
			$php[] = '			}';
			$php[] = '		}';
		}
		$php[] = '		return true;';
		return join(PHP_EOL, $php);
	}


	private function buildPhpDecl($value)
	{
		if (is_string($value))
		{
			return '"'.$value.'"';
		}
		else if (is_bool($value))
		{
			return $value ? 'true' : 'false';
		}
		else if (is_array($value))
		{
			$str = array();
			foreach ($value as $k => $v)
			{
				if (is_integer($k))
				{
					$str[] = $k.' => "'.addslashes($v).'"';
				}
				else
				{
					$str[] = '"'.$k.'" => "'.addslashes($v).'"';
				}
			}
			return 'array(' . join(', ', $str) . ')';
		}
		else
		{
			return $value;
		}
	}

	/**
	 * @return String
	 */
	public function getCommentaryType()
	{
		if ($this->isDocument())
		{
			if ($this->getDocumentType() === generator_PersistentModel::BASE_MODEL)
			{
				return generator_PersistentModel::BASE_CLASS_NAME;
			}
			
			list ($package, $docName) = explode('/', $this->getDocumentType());
			list (, $packageName) = explode('_', $package);
			return $packageName . "_persistentdocument_" . $docName;
		}
		else
		{
			switch ($this->getType())
			{
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN :
					return 'boolean';
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOUBLE :
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DECIMAL :
					return 'float';
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER :
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOCUMENTID :
					return 'integer';
				default:
					return 'string';
			}
		}
	}
	
	/**
	 * @return string|NULL
	 */
	public function getCommentaryDocumentType()
	{
		if ($this->getDocumentType() === generator_PersistentModel::BASE_MODEL)
		{
			return generator_PersistentModel::BASE_CLASS_NAME;
		}
		elseif ($this->getDocumentType() !== null)
		{
			list ($package, $docName) = explode('/', $this->getDocumentType());
			list (, $packageName) = explode('_', $package);
			return "" . $packageName . "_persistentdocument_" . $docName;
		}
		return null;
	}
	/**
	 * @return String
	 */
	public function getRelationName()
	{
		return $this->relationName;
	}

	/**
	 * @return string
	 */
	public function getDbSize()
	{
		return $this->dbSize;
	}
}

<?php
/**
 * @package framework.builder.generator
 */
class generator_PersistentProperty
{
	/**
	 * @var generator_PersistentModel
	 */
	private $model;

	private $id;
	/**
	 * @var Boolean
	 */
	private $indexed = false;
	/**
	 * @var Boolean
	 */
	private $specificIndex = false;
	private $name;
	private $type;
	private $typeModel; // Used for inverse properties sorting and set only for them.
	private $minOccurs;
	private $maxOccurs;
	private $dbMapping;
	private $dbMappingOci;
	private $dbSize;

	private $fromList;
	private $primaryKey;
	private $cascadeDelete;

	private $defaultValue;
	private $inverse;

	private $constraints;
	private $treeNode;
	private $treeNodeInverse;
	private $localized;

	private $preserveOldValue;

	private $relationName;
	private $tableName;
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
				case "id":
					$this->id = $value;
					break;
				case "indexed":
					$this->indexed = generator_PersistentModel::getBoolean($value);
					break;
				case "specific-index":
					$this->specificIndex = generator_PersistentModel::getBoolean($value);
					break;
				case "name":
					$this->name = $value;
					break;
				case "type":
					$this->type = $value;
					break;
				case "from-list":
					$this->fromList = $value;
					break;
				case "primary-key":
					$this->primaryKey = generator_PersistentModel::getBoolean($value);
					break;
				case "cascade-delete":
					$this->cascadeDelete = generator_PersistentModel::getBoolean($value);
					break;
				case "default-value":
					$this->defaultValue = $value;
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
				case "db-mapping-oci":
					$this->dbMappingOci = $value;
					break;
				case "db-size":
					$this->dbSize = intval($value);
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
			if ($node->nodeName == 'constraints')
			{
				$this->constraints = strval($node->nodeValue);
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
		$property->minOccurs = 1;
		$property->maxOccurs = 1;
		$property->type = f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER;
		$property->primaryKey = true;
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
		$property->minOccurs = 1;
		$property->maxOccurs = 1;
		$property->type = f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING;
		$property->primaryKey = false;
		$property->localized = false;

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
		$property->maxOccurs = 1;
		$property->minOccurs = 0;
		$property->type = f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER;
		$property->primaryKey = false;
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
		$property->maxOccurs = 1;
		$property->minOccurs = 0;
		$property->type = f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER;
		$property->primaryKey = false;
		$property->localized = false;
		return $property;
	}
	
	/**
	 * @param generator_PersistentModel $document
	 * @param boolean $localized
	 */
	public static function generateS18sProperty($model, $localized = false)
	{
		$property = new generator_PersistentProperty($model);
		$property->cascadeDelete = false;
		$property->name = 's18s';
		$property->dbMapping = 'document_s18s';
		$property->maxOccurs = 1;
		$property->minOccurs = 0;
		$property->type = f_persistentdocument_PersistentDocument::PROPERTYTYPE_LOB;
		$property->primaryKey = false;
		$property->localized = $localized;
		return $property;
	}	

	/**
	 * @param generator_PersistentProperty $property
	 */
	public function generateInverseProperty($property)
	{
		$document = generator_PersistentModel::getModelByName($property->getType());
		$invertProperty = new generator_PersistentProperty($document);
		$tmp = explode('/', $property->model->getName());
		$invertProperty->name = $tmp[1];
		$invertProperty->type = $property->model->getName();
		$invertProperty->typeModel = $property->model;
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
	 * Used for inverse properties sorting.
	 * @param generator_PersistentModel $model
	 */
	public function setTypeModel($model)
	{
		$this->typeModel = $model;
	}
	
	/**
	 * @param String $constraints
	 * @return String
	 */
	private function reduceConstraints($constraints)
	{
		if (is_null($constraints) || $constraints == '')
		{
			return null;
		}

		$constraints = explode(';', $constraints);
		$newconstraints = array();
		foreach ($constraints as $constraint)
		{
			$pos = strpos($constraint, ':');
			if ($pos === false)
			{
				continue;
			}
			$name = substr($constraint, 0, $pos);
			$newconstraints[$name] = $constraint;
		}
		if (count($newconstraints) > 0)
		{
			return join(';', $newconstraints);
		}
		return null;
	}

	/**
	 * @return Boolean
	 */
	public function isDocument()
	{
		return DocumentHelper::isDocumentProperty($this->type);
	}

	/**
	 * @return Boolean
	 */
	public function isLocalized()
	{
		return (!is_null($this->localized) && $this->localized);
	}

	/**
	 * @return void
	 */
	public function setLocalized()
	{
		$this->localized = true;
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
	 * @return String
	 */
	public function getId()
	{
		if ($this->id !== null)
		{
			return $this->id;
		}
		return $this->name;
	}

	/**
	 * @return Boolean
	 */
	public function isIndexed()
	{
		return $this->indexed;
	}

	/**
	 * @return Boolean
	 */
	public function hasSpecificIndex()
	{
		return $this->specificIndex;
	}

	/**
	 * @param generator_PersistentProperty $property
	 */
	public function mergeGeneric($property)
	{
		$this->type = $property->type;
		$this->dbMapping = $property->dbMapping;
		$this->dbMappingOci = $property->dbMappingOci;
		$this->maxOccurs = $property->maxOccurs;
		if (is_null($this->dbSize)) {$this->dbSize = $property->dbSize;}
		if (is_null($this->minOccurs)) {$this->minOccurs = $property->minOccurs;}
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
		$this->primaryKey = $parentProperty->primaryKey;
		$this->cascadeDelete = $parentProperty->cascadeDelete;
		$this->dbMapping = $parentProperty->dbMapping;
		$this->dbMappingOci = $parentProperty->dbMappingOci;
		$this->treeNode = $parentProperty->treeNode;
		$this->inverse = $parentProperty->inverse;
	}

	/**
	 * @return Boolean
	 */
	public function isOverride()
	{
		return !is_null($this->parentProperty);
	}

	public function applyDefaultConstraints()
	{

		if ($this->isOverride() && f_util_StringUtils::isNotEmpty($this->parentProperty->getConstraints()))
		{
			$constraints = explode(';', $this->parentProperty->getConstraints());
		}
		else 
		{
			$constraints = array();
		}
		
		if ($this->getMinOccurs() != 0)
		{
			$constraints[] = "blank:false";
		}

		if ($this->type == f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING)
		{
			if (is_null($this->dbSize)) {$this->dbSize = 255;}
			$constraints[] = "maxSize:".$this->dbSize;
		}

		if (!is_null($this->constraints))
		{
			$constraints[] = $this->constraints;
		}

		if (count($constraints) != 0)
		{
			$this->constraints = $this->reduceConstraints(implode(';', $constraints));
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
	 * @param generator_PersistentProperty $property
	 */
	public function override($property)
	{
		if (!is_null($property->fromList)) {$this->fromList = $property->fromList;}
		if (!is_null($property->defaultValue)) {$this->defaultValue = $property->defaultValue;}
		if (!is_null($property->preserveOldValue)) {$this->preserveOldValue = $property->preserveOldValue;}
		
		// TODO: maybe we should check here that the new model extends the the previous one.   
		if (!is_null($property->type) && ($this->isDocument() && $property->isDocument()))
		{
			$this->type = $property->type;
		}
		if (!is_null($property->minOccurs) && ($this->minOccurs < $property->minOccurs))
		{
			$this->minOccurs = $property->minOccurs;
		}
		if (!is_null($property->maxOccurs) && $this->maxOccurs != 1 && $property->maxOccurs != 1)
		{
			$this->maxOccurs = $property->maxOccurs;
		}
		
		if (!is_null($property->constraints))
		{
			if (is_null($this->constraints))
			{
				$this->constraints = $property->constraints;
			}
			else
			{
				$this->constraints = $this->reduceConstraints($this->constraints . ';' . $property->constraints);
			}
		}
	}

	/**
	 * @return Integer
	 */
	public function getMinOccurs()
	{
		return is_null($this->minOccurs) ? 0 : $this->minOccurs;
	}

	/**
	 * @return Integer
	 */
	public function getMaxOccurs()
	{
		return is_null($this->maxOccurs) ? 1 : $this->maxOccurs;
	}

	/**
	 * @return String
	 */
	public function getDbName()
	{
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		if ($pp instanceof f_persistentdocument_PersistentProviderOci && $this->dbMappingOci)
		{
			return $this->dbMappingOci;
		}
		return (is_null($this->dbMapping)) ?  strtolower($this->name) : $this->dbMapping;
	}

	/**
	 * @return Boolean
	 */
	public function isPrimaryKey()
	{
		return is_null($this->primaryKey) ? false : $this->primaryKey;
	}

	/**
	 * @return String
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @return String
	 */
	public function getConstraints()
	{
		return $this->constraints;
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
		return $this->getMaxOccurs() != 1 && $this->isDocument();
	}

	/**
	 * @return Boolean
	 */
	public function isBackofficeFullTextProperty()
	{
		$toSkip = array('author' => true, 'lang' => true , 'model' => true, 'publicationstatus' => true, 'modelversion' =>true);
		$type =  $this->getType();
		if ($type == 'String' || $type == 'LongString' || $type == 'XHTMLFragment')
		{
			return !array_key_exists($this->getName(), $toSkip);
		}
		return false;
	}

	/**
	 * TODO: allow a property to be excluded from being indexed
	 *
	 * @return Boolean
	 */
	public function isBackofficeIndexable()
	{
		return true;
	}

	/**
	 * @return String
	 */
	public function getFromList()
	{
		if (is_null($this->fromList) && !is_null($this->parentProperty))
		{
			return $this->parentProperty->fromList;
		}
		else
		{
			return $this->fromList;
		}
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
		$ret = '		$this->set' . ucfirst($this->name) . '(';
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
		$uName = ucfirst($name);
		if (is_null($this->constraints) || in_array($name, array('id', 'model', 'lang')))
		{
			return;
		}
		
		if ($this->isOverride() && $this->constraints == $this->parentProperty->constraints)
		{
			return;
		}
		
		$phpScript[] = '	protected function is' . $uName .'Valid()';
		$phpScript[] = '	{';
		if ($this->isDocument())
		{

			$phpScript[] = '		$uniqueValidatorResult = true;';

			if ( ! $this->isArray() )
			{
				$str = $this->constraints;

				$constraintsParser = new validation_ContraintsParser();
				$validators = $constraintsParser->getValidatorsFromDefinition($str);

				$validatorScript = array();
				foreach ($validators as $validator)
				{
					if ($validator instanceof validation_UniqueValidator)
					{
						$validatorScript[] = '			$errCount = $this->validationErrors->count();';
						$validatorScript[] = '			$v = new '.get_class($validator).'();';
						$validatorScript[] = '			$v->setDocument($this);';
						$validatorScript[] = '			$v->setDocumentPropertyName("'.$this->name.'");';
						$parameter = $validator->getParameter();
						if (!is_object($parameter))
						{
							$parameter = self::buildPhpDecl($parameter);
						}
						$validatorScript[] = '			$v->setParameter(' . $parameter . ');';
						if ($validator->usesReverseMode())
						{
							$validatorScript[] = '			$v->setReverseMode(true);';
						}
						$validatorScript[] = '			$property = new validation_Property("'.$name.'", $this->get'.$uName.'()->getId());';
						$validatorScript[] = '			$v->validate($property, $this->validationErrors);';
						$validatorScript[] = '        	$uniqueValidatorResult = $this->validationErrors->count() == $errCount;';
					}
				}

				if (!empty($validatorScript))
				{
					$phpScript[] = '		if (!is_null($this->get'.$uName.'()))';
					$phpScript[] = '		{';
					$phpScript = array_merge($phpScript, $validatorScript);
					$phpScript[] = '		}';

				}

				if ($this->getMinOccurs() > 0)
				{
					$phpScript[] = '		if ($this->m_' . $name.' !== null && is_numeric($this->m_' . $name.')) return $uniqueValidatorResult;';
				}
				else
				{
					$phpScript[] = '		if ($this->m_' . $name.' === null || is_numeric($this->m_' . $name.')) return $uniqueValidatorResult;';
				}
				$phpScript[] = '		$this->checkLoaded' . $uName .'();';
				$phpScript[] = '		return $this->m_' . $name .'->isValid('. $this->getMinOccurs() .', '. $this->getMaxOccurs() .', \''.$this->type.'\') && $uniqueValidatorResult;';
			}
			else
			{
				$phpScript[] = '		$this->checkLoaded' . $uName .'();';
				$phpScript[] = '		return $this->m_' . $name .'->isValid('. $this->getMinOccurs() .', '. $this->getMaxOccurs() .', \''.$this->type.'\') && $uniqueValidatorResult;';
			}
		}
		else if (!is_null($this->constraints))
		{
			$phpScript[] = $this->generatePhpValidators($this->constraints);
		}
		else
		{
			$phpScript[] = '		return true;';
		}
		$phpScript[] = '	}';

		return join("\n", $phpScript);
	}

	private function generatePhpValidators()
	{
		$str = $this->constraints;

		$name = $this->getName();
		$uName = ucfirst($name);
		$php = array();
		$constraintsParser = new validation_ContraintsParser();
		$validators = $constraintsParser->getValidatorsFromDefinition($str);

		$blankValidatorFound = false;
		foreach ($validators as $validator)
		{
			$validateCall = '		$v->validate($property, $this->validationErrors);';

			// BlankValidator should be executed first!
			if ($validator instanceof validation_BlankValidator && $validator->getParameter() === false)
			{
				// be careful with array_unshift: order is reversed!
				if (count($validators) > 1)
				{
					array_unshift($php, '		{');
					array_unshift($php, '		if ( ! empty($propertyValue) )');
				}
				array_unshift($php, $validateCall);
				array_unshift($php, '		$v->setParameter(false);');
				array_unshift($php, '		$v = new '.get_class($validator).'();');
				$blankValidatorFound = true;
			}
			else
			{
				$php[] = '			$v = new '.get_class($validator).'();';
				$parameter = $validator->getParameter();
				if ($parameter instanceof validation_Range)
				{
					$parameter = 'new validation_Range('.self::buildPhpDecl($parameter->getMin()).', '.self::buildPhpDecl($parameter->getMax()).')';
				}
				else if (!is_object($parameter))
				{
					$parameter = self::buildPhpDecl($parameter);
				}
				$php[] = '			$v->setParameter(' . $parameter . ');';
				if ($validator->usesReverseMode())
				{
					$php[] = '			$v->setReverseMode(true);';
				}
				if ($validator instanceof validation_UniqueValidator)
				{
					$php[] = '			// Specifically for UniqueValidator:';
					$php[] = '			$v->setDocument($this);';
					$php[] = '			$v->setDocumentPropertyName("'.$this->name.'");';
				}

				$php[] = '	' . $validateCall;
			}
		}

		if ( (! $blankValidatorFound && count($validators) >= 1) || ($blankValidatorFound && count($validators) > 1) )
		{
			if ( ! $blankValidatorFound )
			{
				// be careful with array_unshift: order is reversed!
				array_unshift($php, '		{');
				array_unshift($php, '		if ( ! empty($propertyValue) )');
			}
			$php[] = '		}';
		}

		$override = $this->isOverride() ? ' && parent::is' . $uName .'Valid();' : ';';
		$php[] = '		return $this->validationErrors->count() == $errCount' . $override;

		// be careful with array_unshift: order is reversed!
		array_unshift($php, '		$property = new validation_Property("'.$name.'", $propertyValue);');
		array_unshift($php, '		$propertyValue = $this->get'.$uName.'();');
		array_unshift($php, '		$errCount = $this->validationErrors->count();');

		return join("\n", $php);
	}


	private static function buildPhpDecl($value)
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
			if ($this->getType() == generator_PersistentModel::BASE_MODEL)
			{
				return generator_PersistentModel::BASE_CLASS_NAME;
			}
			$res = Framework::parseComponentType($this->getType());
			return "" .$res["package_name"] . "_persistentdocument_" . $res["component"];
		}
		else
		{
			switch ($this->getType())
			{
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN :
					return 'Boolean';
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOUBLE :
					return 'Double';
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER :
					return 'Integer';
				default:
					return 'String';
			}
		}
	}

	/**
	 * @return String
	 */
	public function getRelationName()
	{
		return $this->relationName;
	}

	/**
	 * @return Integer
	 */
	private function getDbSize()
	{
		return is_null($this->dbSize) ? 255 : $this->dbSize;
	}

	/**
	 * @return String
	 */
	public function generateSql($type, $localized = false)
	{
		if ($type == 'mysql')
		{
			$localizedSuffix = $localized ? '_i18n' : '';
			$field = '  `' . $this->getDbName() . $localizedSuffix . '`';
			if ($this->getDbName() === 'document_publicationstatus')
			{
				return $field . " ENUM('DRAFT', 'CORRECTION', 'ACTIVE', 'PUBLICATED', 'DEACTIVATED', 'FILED', 'DEPRECATED', 'TRASH', 'WORKFLOW') NULL DEFAULT NULL";
			}
			
			if ($this->isDocument())
			{
				if ($this->isArray())
				{
					$field .= ' int(11) default \'0\'';
				}
				else
				{
					$field .= ' int(11) default NULL';
				}
				return $field;
			}
			switch ($this->type)
			{
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING:
					$field .= " varchar(". $this->getDbSize() .")";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_LONGSTRING:
					$field .= " text";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT:
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_LOB:
					$field .= " mediumtext";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN:
					$field .= " tinyint(1) NOT NULL default '0'";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DATETIME:
					$field .= " datetime";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOUBLE:
					$field .= " double";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER:
					$field .= " int(11)";
					break;
			}
			return $field;
		}
		else if ($type == 'oci')
		{
			$localizedSuffix = $localized ? '_i18n' : '';
			$field = '  "' . $this->getDbName() . $localizedSuffix . '"';
			if ($this->isDocument())
			{
				if ($this->isArray())
				{
					$field .= ' NUMBER(11) default(0)';
				}
				else
				{
					$field .= ' NUMBER(11)';
				}
				return $field;
			}
			switch ($this->type)
			{
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_STRING:
					$field .= " VARCHAR2(". $this->getDbSize() .")";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_LOB:
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_LONGSTRING:
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT:
					$field .= " CLOB DEFAULT(EMPTY_CLOB())";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN:
					$field .= " NUMBER(1) default(0)";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DATETIME:
					$field .= " CHAR(19)";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DOUBLE:
					$field .= " NUMBER(13,2)";
					break;
				case f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER:
					$field .= " NUMBER(11)";
					break;
			}
			return $field;
		}
		return '';
	}
}

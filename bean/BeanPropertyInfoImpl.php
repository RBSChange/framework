<?php
/**
 * A basic BeanPropertyInfo implementation
 */
class BeanPropertyInfoImpl implements BeanPropertyInfo
{
	/**
	 * @var String
	 */
	private $name;
	/**
	 * @var String
	 */
	private $type;
	/**
	 * @var String
	 */
	private $documentType;

	/**
	 * @var String
	 */
	private $className;
	
	/**
	 * @var Integer
	 */
	private $cardinality = 1;
	/**
	 * @var mixed
	 */
	private $defaultValue;
	/**
	 * @var Boolean
	 */
	private $isHidden = false;
	/**
	 * @var Boolean
	 */
	private $isRequired = false;
	/**
	 * @var String
	 */
	private $labelKey;
	/**
	 * @var String
	 */
	private $helpKey;
	/**
	 * @var Object
	 */
	private $list;

	/**
	 * @var String
	 */
	private $listId;

	/**
	 * @var Integer
	 */
	private $minOccurs = 0;
	
	/**
	 * @var array<String, String>
	 */
	private $constraints;
	
	/**
	 * @var Boolean
	 */
	private $isPublic = false;
	
	/**
	 * @var String
	 */
	private $setterName = false;

	/**
	 * @param String $name
	 * @param String $type
	 * @param String $className required if type is BeanPropertyType::BEAN
	 */
	function __construct($name, $type, $className = null)
	{
		$this->name = $name;
		if (f_util_StringUtils::beginsWith($type, "modules_"))
		{
			$this->type = BeanPropertyType::DOCUMENT;
			$this->documentType = $type;
		}
		else
		{
			if ($type === BeanPropertyType::BEAN || $type === BeanPropertyType::CLASS_TYPE || $type === BeanPropertyType::DOCUMENT)
			{
				if ($className === null)
				{
					throw new Exception(__METHOD__." you must define the className argument for type ".BeanPropertyType::BEAN." or ".BeanPropertyType::CLASS_TYPE);
				}
				$this->className = $className;
				if ($type === BeanPropertyType::DOCUMENT)
				{
					$matches = null;
					if (preg_match('/(\w+)_persistentdocument_(\w+)$/', $className, $matches))
					{
						$this->documentType = 'modules_'.$matches[1].'/'.$matches[2];
					}
					else
					{
						throw new Exception("Unable to parse $className");
					}
				}
			}
			$this->type = $type;
		}
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
	public function getType()
	{
		return $this->type;
	}

	/**
	 * If the property type is BeanPropertyType::DOCUMENT,
	 * returns the linked document model
	 * @return String
	 */
	public function getDocumentType()
	{
		return $this->documentType;
	}

	/**
	 * (non-PHPdoc)
	 * @see f_mvc/bean/BeanPropertyInfo#getClassName()
	 */
	function getClassName()
	{
		return $this->className;
	}

	/**
	 * @return String
	 */
	public function getValidationRules()
	{
		if (f_util_ArrayUtils::isEmpty($this->constraints))
		{
			return null;
		}
		$constraintsInLine = array();
		foreach ($this->constraints as $validatorName => $validatorParam)
		{
			$constraintsInLine[] = $validatorName.":".$validatorParam;
		} 
		return $this->name."{".join(";", $constraintsInLine)."}";
	}

	/**
	 * @param string $validationRules
	 */
	function setValidationRules($validationRules)
	{
		$this->validationRules = $validationRules;
		$parser = new validation_ContraintsParser();
		$matches = array();
		if (!preg_match('/^\w+\{(.*)\}$/', $validationRules, $matches))
		{
			throw new Exception("Unable to parse $validationRules as validationRules");
		}
		else
		{
			$this->constraints = $parser->getConstraintArrayFromDefinition($matches[1]);	
		}
	}
	
	/**
	 * @param String $constraint
	 * @return void
	 */
	function addConstraint($validatorName, $validatorParam)
	{
		if ($this->constraints === null)
		{
			$this->constraints = array();
		}
		$this->constraints[$validatorName] = $validatorParam;
	}
	
	function getConstraints()
	{
		return $this->constraints;
	}

	/**
	 * @return mixed
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	function setDefaultValue($defaultValue)
	{
		$this->defaultValue = $defaultValue;
	}

	/**
	 * @return String
	 */
	public function getLabelKey()
	{
		return $this->labelKey;
	}

	/**
	 * @param String $helpKey
	 */
	public function setLabelKey($labelKey)
	{
		$this->labelKey = $labelKey;
	}

	/**
	 * @return String
	 */
	public function getHelpKey()
	{
		return $this->helpKey;
	}

	/**
	 * @param String $helpKey
	 */
	public function setHelpKey($helpKey)
	{
		$this->helpKey = $helpKey;
	}

	/**
	 * @return Integer >=1 | -1, meaning "infinite"
	 */
	public function getCardinality()
	{
		return $this->cardinality;
	}

	function setCardinality($cardinality)
	{
		$this->cardinality = $cardinality;
	}

	/**
	 * @return Boolean
	 */
	public function isRequired()
	{
		return $this->isRequired;
	}

	/**
	 * @param Boolean $isRequired
	 */
	function setIsRequired($isRequired)
	{
		$this->isRequired = $isRequired;
		if ($isRequired)
		{
			$this->addConstraint("blank", "false");
		}
		elseif (isset($this->constraints["blank"]))
		{
			unset($this->constraints["blank"]);
		}
	}

	/**
	 * @return Boolean
	 */
	public function isHidden()
	{
		return $this->isHidden;
	}

	/**
	 * @param Boolean $isHidden
	 */
	function setIsHidden($isHidden)
	{
		$this->isHidden = $isHidden;
	}

	/**
	 * @see BeanPropertyInfo::getConverter()
	 *
	 * @return BeanValueConverter or null
	 */
	public function getConverter()
	{
		switch ($this->getType())
		{
			case BeanPropertyType::DATETIME:
				return new bean_DateTimeConverter();
			case BeanPropertyType::XHTMLFRAGMENT:
				return new bean_XHTMLFragmentConverter();
			case BeanPropertyType::BOOLEAN:
				return new bean_BooleanConverter();
			case BeanPropertyType::DOUBLE:
				return new bean_DecimalConverter();
			case BeanPropertyType::INTEGER:
				return new bean_IntegerConverter();
			case BeanPropertyType::DOCUMENT:
				if ($this->cardinality === 1)
				{
					return new bean_DocumentConverter();
				}
				return new bean_DocumentsConverter();
		}
		return null;
	}

	/**
	 * @return Boolean
	 */
	public function hasList()
	{
		return $this->list !== null || $this->listId !== null;
	}

	/**
	 * TODO: interface for list
	 * @return Object
	 */
	public function getList()
	{
		if ($this->listId !== null && $this->list === null)
		{
			$list = list_ListService::getInstance()->getByListId($this->listId);
			if ($list === null)
			{
				throw new Exception("Could not find list '".$this->listId."'");
			}
			$this->list = $list;
		}
		return $this->list;
	}

	/**
	 * @param Object $list
	 */
	function setList($list)
	{

		$this->list = $list;
	}

	/**
	 * @param String $listId
	 */
	function setListId($listId)
	{
		$this->listId = $listId;
	}

	/**
	 * @return Integer
	 */
	public function getMaxOccurs()
	{
		return $this->getCardinality();
	}

	/**
	 * @return Integer
	 */
	public function getMinOccurs()
	{
		return $this->minOccurs;
	}

	/**
	 * @param Integer $maxOccurs
	 */
	public function setMaxOccurs($maxOccurs)
	{
		$this->setCardinality($maxOccurs);
	}

	/**
	 * @param Integer $minOccurs
	 */
	public function setMinOccurs($minOccurs)
	{
		$this->minOccurs = $minOccurs;
	}

	/**
	 * @see BeanPropertyInfo::getSetterName()
	 * @return String|null if property has public access
	 */
	public function getSetterName()
	{
		if ($this->setterName !== false)
		{
			return $this->setterName;
		}
		if ($this->isPublic)
		{
			return null;
		}
		return 'set' . ucfirst($this->name);
	}
	
	/**
	 * @param String $setterName
	 */
	function setSetterName($setterName)
	{
		$this->setterName = $setterName;
	}
	
	/**
	 * @return Boolean
	 */
	public function isPublic()
	{
		return $this->isPublic;
	}
	
	/**
	 * @param Boolean $isPublic
	 */
	public function setIsPublic($isPublic)
	{
		$this->isPublic = $isPublic;
	}

	/**
	 * @see BeanPropertyInfo::getGetterName()
	 * @return String|null if property has public access
	 */
	public function getGetterName()
	{
		if ($this->isPublic)
		{
			return null;
		}
		if (BeanPropertyType::XHTMLFRAGMENT == $this->getType())
		{
			//return 'get' . ucfirst($this->getName()) . 'AsHtml';
		}
		return 'get' . ucfirst($this->getName());
	}
}
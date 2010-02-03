<?php
class f_persistentdocument_PersistentDocumentBeanPropertyInfo implements BeanPropertyInfo
{
	/**
	 * @var PropertyInfo
	 */
	private $propertyInfo;
	
	/**
	 * @var FormPropertyInfo
	 */
	private $formPropertyInfo;
	
	/**
	 * @var String
	 */
	private $moduleName;
	
	/**
	 * @var String
	 */
	private $documentName;
	
	private $cardinality;
	private $defaultValue;
	private $name;
	private $type;
	/**
	 * @var String Validation rules
	 */
	private $constraints;
	/**
	 * @var String
	 */
	private $documentType;
	/**
	 * @var String
	 */
	private $className;
	
	public function __construct($moduleName, $documentName, $propertyInfo, $formPropertyInfo)
	{
		$this->propertyInfo = $propertyInfo;
		$this->formPropertyInfo = $formPropertyInfo;
		$this->moduleName = $moduleName;
		$this->documentName = $documentName;
	}
	
	/**
	 * @return Integer
	 */
	public function getMaxOccurs()
	{
		return $this->propertyInfo->getMaxOccurs();
	}
	
	/**
	 * @return Integer
	 */
	public function getMinOccurs()
	{
		return $this->propertyInfo->getMinOccurs();
	}
	
	/**
	 * @see BeanPropertyInfo::getCardinality()
	 *
	 * @return Integer
	 */
	public function getCardinality()
	{
		if ($this->cardinality === null)
		{
			$this->cardinality = $this->propertyInfo->getMaxOccurs();
		}
		return $this->cardinality;
	}
	
	/**
	 * @see BeanPropertyInfo::getDefaultValue()
	 *
	 * @return mixed
	 */
	public function getDefaultValue()
	{
		if ($this->defaultValue === null)
		{
			$this->defaultValue = $this->propertyInfo->getDefaultValue();
		}
		return $this->defaultValue;
	}
	
	/**
	 * @see BeanPropertyInfo::getLabelKey()
	 *
	 * @return String
	 */
	public function getLabelKey()
	{
		return '&modules.' . $this->moduleName . '.document.' . $this->documentName . '.' . ucfirst($this->getName()) . ';';
	}
	
	/**
	 * @see BeanPropertyInfo::getHelpKey()
	 *
	 * @return String
	 */
	public function getHelpKey()
	{
		return '&modules.' . $this->moduleName . '.document.' . $this->documentName . '.' . ucfirst($this->getName()) . '-help;';
	}
	
	/**
	 * @see BeanPropertyInfo::getName()
	 *
	 * @return String
	 */
	public function getName()
	{
		if ($this->name === null)
		{
			$this->name = $this->propertyInfo->getName();
		}
		return $this->name;
	}
	
	/**
	 * @see BeanPropertyInfo::getType()
	 *
	 * @return String
	 */
	public function getType()
	{
		if ($this->type === null)
		{
			if ($this->propertyInfo->isDocument())
			{
				$this->type = BeanPropertyType::DOCUMENT;
				$this->documentType = $this->propertyInfo->getType();
				$matches = null;
				if (preg_match('/^modules_(\w+)\/(\w+)$/', $this->documentType, $matches))
				{
					$this->className = $matches[1]."_persistentdocument_".$matches[2];	
				}
				else
				{
					throw new Exception("Could not parse document type ".$this->documentType);
				}
			}
			else
			{
				$this->type = $this->propertyInfo->getType();
			}
		}
		return $this->type;
	}
	
	/**
	 * If the property type is BeanPropertyType::DOCUMENT,
	 * returns the linked document model
	 * @return String
	 */
	public function getDocumentType()
	{
		// getType() must be called before getDocumentType()
		// because getType() fill documentType.
		$this->getType();
		return $this->documentType;
	}
	
	/**
	 * If the property type if DOCUMENT, BEAN or CLASS
	 * @return String
	 */
	public function getClassName()
	{
		// getType() must be called before getClassName()
		// because getType() fill documentType.
		$this->getType();
		return $this->className;
	}
	
	/**
	 * @see BeanPropertyInfo::getValidationRules()
	 *
	 * @return String
	 */
	public function getValidationRules()
	{
		if ($this->constraints === null)
		{
			$constraints = $this->propertyInfo->getConstraints();
			if (f_util_StringUtils::isEmpty($constraints))
			{
				$this->constraints = "";
			}
			else
			{
				$this->constraints = $this->getName() . '{' . $this->propertyInfo->getConstraints() . '}';	
			}
		}
		return $this->constraints;
	}
	
	/**
	 * @return FormPropertyInfo
	 */
	public function getFormPropertyInfo()
	{
		return $this->formPropertyInfo;
	}
	
	/**
	 * @see BeanPropertyInfo::isRequired()
	 *
	 * @return Boolean
	 */
	public function isRequired()
	{
		if ($this->propertyInfo->getMinOccurs() > 0)
		{
			return true;
		}
		
		if ($this->formPropertyInfo !== null)
		{
			return $this->formPropertyInfo->isRequired();
		}
		return false;
	}
	
	/**
	 * @see BeanPropertyInfo::isHidden()
	 *
	 * @return Boolean
	 */
	public function isHidden()
	{
		$propertyName = $this->propertyInfo->getName();
		if ($propertyName == 'id' || $propertyName == 'model')
		{
			return true;
		}
		
		if ($this->formPropertyInfo !== null)
		{
			return $this->formPropertyInfo->isHidden();
		}
		return false;
	}
	

	/**
	 * @see BeanPropertyInfo::getConverter()
	 * 
	 * @return BeanValueConverter or null
	 */
	public function getConverter()
	{
		if ($this->hasList())
		{
			$list = $this->getList();
			if ($list instanceof list_persistentdocument_editablelist)
			{
				return new bean_EditableListConverter($list);
			}
		}
		
		if ($this->propertyInfo->isDocument())
		{
			if ($this->propertyInfo->isArray())
			{
				return new bean_DocumentsConverter();
			}
			return new bean_DocumentConverter();
		}
		
		switch ($this->getType())
		{
			case BeanPropertyType::DATETIME:
				return new bean_DateTimeConverter();
			case BeanPropertyType::XHTMLFRAGMENT:
				return new bean_XHTMLFragmentConverter();
			case BeanPropertyType::DOUBLE:
				return new bean_DecimalConverter();	
			case BeanPropertyType::BOOLEAN:
				return new bean_BooleanConverter();
			case BeanPropertyType::INTEGER:
				return new bean_IntegerConverter();
		}
		return null;
	}
	
	private function isDocument()
	{
		return $this->propertyInfo->isDocument();
	}

	private $hasAttachedList;
	private $attachedList;
	
	/**
	 * @see BeanPropertyInfo::getList()
	 *
	 */
	public function getList()
	{
		if (!$this->hasList())
		{
			throw new Exception("Property has no attached list");
		}
		
		if ($this->attachedList === null)
		{
			$extraAttributes = $this->formPropertyInfo->getAttributes();
			$this->attachedList = list_ListService::getInstance()->getByListId($extraAttributes['list-id']);
		}
		return $this->attachedList;
	}
	
	/**
	 * @see BeanPropertyInfo::hasList()
	 *
	 * @return Boolean
	 */
	public function hasList()
	{
		if ($this->hasAttachedList === null)
		{
			$this->hasAttachedList = false;
			if ($this->formPropertyInfo !== null)
			{
				$extraAttributes = $this->formPropertyInfo->getAttributes();
				if (isset($extraAttributes['list-id']))
				{
					$this->hasAttachedList = true;
				}
			}
		}
		return $this->hasAttachedList;
	}
	
	/**
	 * @see BeanPropertyInfo::getSetterName()
	 * @return String
	 */
	public function getSetterName()
	{
		if ($this->propertyInfo->isArray())
		{
			return 'set' . ucfirst($this->getName()) . 'Array';
		}
		return 'set' . ucfirst($this->getName());
	}
	
	/**
	 * @see BeanPropertyInfo::getGetterName()
	 * @return String
	 */
	public function getGetterName()
	{
		if ($this->propertyInfo->isArray())
		{
			return 'get' . ucfirst($this->getName()) . 'Array';
		}
		
		if (BeanPropertyType::XHTMLFRAGMENT == $this->getType())
		{
			return 'get' . ucfirst($this->getName()) . 'AsHtml';
		}
		return 'get' . ucfirst($this->getName());
	}
}
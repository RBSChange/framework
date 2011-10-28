<?php
class BeanAggregatePropertyInfo implements BeanPropertyInfo
{
	/**
	 * @var BeanPropertyInfo
	 */
	private $wrappedBeanPropertyInfo;
	private $beanName;

	/**
	 * @see BeanPropertyInfo::getList()
	 *
	 */
	public function getList()
	{
		return $this->wrappedBeanPropertyInfo->getList();
	}

	/**
	 * @see BeanPropertyInfo::hasList()
	 *
	 * @return Boolean
	 */
	public function hasList()
	{
		return $this->wrappedBeanPropertyInfo->hasList();
	}

	/**
	 *
	 */
	function __construct($beanName, BeanPropertyInfo $propertyInfo)
	{
		$this->wrappedBeanPropertyInfo = $propertyInfo;
		$this->beanName = $beanName;
	}

	/**
	 *
	 * @return Integer >=1 | -1, meaning "infinite"
	 * @see BeanPropertyInfo::getCardinality()
	 */
	public function getCardinality()
	{
		return $this->wrappedBeanPropertyInfo->getCardinality();
	}

	/**
	 *
	 * @return mixed
	 * @see BeanPropertyInfo::getDefaultValue()
	 */
	public function getDefaultValue()
	{
		return $this->wrappedBeanPropertyInfo->getDefaultValue();
	}

	/**
	 *
	 * @return String
	 * @see BeanPropertyInfo::getLabelKey()
	 */
	public function getLabelKey()
	{
		return $this->wrappedBeanPropertyInfo->getLabelKey();
	}

	/**
	 *
	 * @return String
	 * @see BeanPropertyInfo::getName()
	 */
	public function getName()
	{
		return $this->beanName . ucfirst($this->wrappedBeanPropertyInfo->getName());
	}

	/**
	 *
	 * @return String
	 * @see BeanPropertyInfo::getType()
	 */
	public function getType()
	{
		return $this->wrappedBeanPropertyInfo->getType();
	}

	/**
	 * If the property type is BeanPropertyType::DOCUMENT,
	 * returns the linked document model
	 * @return String
	 */
	public function getDocumentType()
	{
		return $this->wrappedBeanPropertyInfo->getDocumentType();
	}
	
	/**
	 * If the property type if DOCUMENT, BEAN or CLASS
	 * @return String
	 */
	public function getClassName()
	{
		return $this->wrappedBeanPropertyInfo->getClassName();
	}

	/**
	 * @return String
	 */
	public function getHelpKey()
	{
		return $this->wrappedBeanPropertyInfo->getHelpKey();
	}

	private $rules;

	/**
	 *
	 * @return String
	 * @see BeanPropertyInfo::getValidationRules()
	 */
	public function getValidationRules()
	{
		$rules = $this->wrappedBeanPropertyInfo->getValidationRules();
        if (!f_util_StringUtils::isEmpty($rules))
        {
        	return $this->beanName . ucfirst($rules);
        }
        return null;
	}

	/**
	 *
	 * @return Boolean
	 * @see BeanPropertyInfo::isRequired()
	 */
	public function isRequired()
	{
		return $this->wrappedBeanPropertyInfo->isRequired();
	}

	/**
	 * @see BeanPropertyInfo::isHidden()
	 *
	 * @return Boolean
	 */
	public function isHidden()
	{
		return $this->wrappedBeanPropertyInfo->isHidden();
	}


	/**
	 * @see BeanPropertyInfo::getConverter()
	 *
	 * @return BeanValueConverter
	 */
	public function getConverter()
	{
		return $this->wrappedBeanPropertyInfo->getConverter();
	}

	/**
	 * @see BeanPropertyInfo::getGetterName()
	 *
	 * @return String
	 */
	public function getGetterName()
	{
		return 'get' . ucfirst($this->beanName) . substr($this->wrappedBeanPropertyInfo->getGetterName(), 3);
	}

	/**
	 * @see BeanPropertyInfo::getMaxOccurs()
	 *
	 * @return Integer
	 */
	public function getMaxOccurs()
	{
		return $this->wrappedBeanPropertyInfo->getMaxOccurs();
	}

	/**
	 * @see BeanPropertyInfo::getMinOccurs()
	 *
	 * @return Integer
	 */
	public function getMinOccurs()
	{
		return $this->wrappedBeanPropertyInfo->getMinOccurs();
	}

	/**
	 * @see BeanPropertyInfo::getSetterName()
	 *
	 * @return String
	 */
	public function getSetterName()
	{
		return 'set' . ucfirst($this->beanName) . substr($this->wrappedBeanPropertyInfo->getSetterName(), 3);
	}
}

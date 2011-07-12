<?php
/**
 * @author intportg
 * @package framework.persistentdocument.filter
 */
class f_persistentdocument_DocumentFilterValueParameter extends f_persistentdocument_DocumentFilterParameter
{
	/**
	 * @var BeanPropertyInfo
	 */
	private $propertyInfo;

	/**
	 * @param BeanPropertyInfo $propertyInfo
	 */
	function __construct($propertyInfo)
	{
		$this->propertyInfo = $propertyInfo;
	}

	/**
	 * @var Mixed
	 */
	private $value;
	
	/**
	 * @return Mixed
	 */
	public function getValue()
	{
		return $this->value;
	}
	
	/**
	 * @param Mixed $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}
	
	/**
	 * @param BeanPropertyInfo $value
	 */
	public function getPropertyInfo()
	{
		return $this->propertyInfo;
	}
	
	/**
	 * @param string $name
	 * @return BeanPropertyInfo
	 */
	public function getPropertyInfoByName($name)
	{
		return $this->getPropertyInfo();
	}
	
	/**
	 * @return String
	 */
	public function getType()
	{
		return $this->propertyInfo->getType();
	}
	
	/**
	 * @return Mixed
	 */
	public function getValueForQuery()
	{
		$this->validate(true);
		$value = null;
		$this->checkType($value);
		return $value;
	}

	/**
	 * @return String
	 */
	public function getValueAsText()
	{
		$value = '...';
		$tmpValue = $this->getValue();
		if ($tmpValue !== null)
		{
			if ($this->getPropertyInfo()->hasList())
			{
				$list = $this->getPropertyInfo()->getList();
				$value = $list->getItemByValue($tmpValue)->getLabel();
			}
			else 
			{
				switch ($this->propertyInfo->getType())
				{
					case BeanPropertyType::DATETIME: 
						$value = date_DateFormat::format(date_Calendar::getInstance($tmpValue), f_Locale::translateUI('&modules.filter.bo.general.date-format;'));
						break;
						
					case BeanPropertyType::DOCUMENT:
						$converter = new bean_DocumentsConverter();
						try 
						{
							$docs = $converter->convertFromRequestToBeanValue($tmpValue);
							$values = array();
							foreach ($docs as $doc) 
							{
								$values[] = $doc->getLabel();
							}
							$value = f_util_StringUtils::shortenString(implode(', ', $values), 60);
						}
						catch (Exception $e)
						{
							if (Framework::isDebugEnabled())
							{
								Framework::exception($e);
							}
							$value = f_Locale::translateUI('&modules.uixul.bo.general.Document-not-found;');
						}
						break;
						
					case BeanPropertyType::BOOLEAN: 
						$value = f_Locale::translateUI('&modules.uixul.bo.general.' . ($tmpValue == 'true' ? 'yes' : 'no') . ';');
						break;
						
					default : 
						$value = $tmpValue;
						break;
				}
			}
		}
		return $value;
	}

	/**
	 * @return Array
	 */
	public function getValueForXul()
	{
		return array(
			'pattern' => '<cFilterParameterValue>' . f_util_HtmlUtils::textToHtml($this->getValueAsText()) . '</cFilterParameterValue>', 
			'value' => $this->getValue()
		);
	}
	
	/**
	 * @return String
	 */
	public function getValueForJson()
	{
		return array(null, null, $this->getValue());
	}
	
	/**
	 * @param Boolean $throwException
	 * @return Boolean
	 * @throws ValidationException
	 */
	public function validate($throwException)
	{
		$value = $this->value;
		if ($value === null || $value === '')
		{
			if ($throwException)
			{
				throw new ValidationException($this->propertyInfo->getName().": no value.");
			}
			return false;
		}
		else if (!$this->checkType($value))
		{
			if ($throwException)
			{
				throw new ValidationException($this->propertyInfo->getName().": invalid type.");
			}
			return false;
		}
		return true;
	}
	
	/**
	 * @return Boolean
	 */
	protected final function checkType(&$value)
	{
		$converter = $this->propertyInfo->getConverter();
		$value = $this->value;
		if ($converter !== null)
		{
			if ($converter instanceof bean_DocumentConverter) 
			{
				$converter = new bean_DocumentsConverter();
			}
			else if ($converter instanceof bean_DateTimeConverter)
			{
				$converter->setDateFormat(date_DateFormat::SQL_DATE_FORMAT);
			}
			if ($converter->isValidRequestValue($value))
			{
				$value = $converter->convertFromRequestToBeanValue($value);
			}
			else
			{
				return false;
			}
		}
		return true;
	}
}
<?php
class validation_SizeValidator extends validation_ValidatorImpl implements validation_Validator
{
	/**
	 * Validate $data and append error message in $errors.
	 *
	 * @param validation_Property $Field
	 * @param validation_Errors $errors
	 *
	 * @return void
	 */
	protected function doValidate(validation_Property $field, validation_Errors $errors)
	{
		$range = $this->getParameter();
		$value = $field->getValue();
		if (is_string($value))
		{
			$len = f_util_StringUtils::strlen($value);
		}
		else if (is_numeric($value))
		{
			$value = strval($value);
			$len = f_util_StringUtils::strlen($value);
		}
		else if (is_array($value))
		{
			$len = count($value);
		}
		else if ($value instanceof ArrayObject)
		{
			$len = $value->count();
		}
		else
		{
			throw new IllegalArgumentException("Field's value must be a string or an array");
		}

		$min = $range->getMin();
		$max = $range->getMax();
		if ($len < $min || $len > $max)
		{
			$this->reject($field->getName(), $errors);
		}
	}


	public function setParameter($value)
	{
		$value = f_util_Convert::fixDataType($value);
		if ( is_integer($value) )
		{
			$value = new validation_Range($value, $value);
		}
		else
		{
			$value = validation_RangeValueParser::getValue($value);
		}
		parent::setParameter($value);
	}


	protected function getMessage()
	{
		return LocaleService::getInstance()->trans($this->getMessageCode(), array(), array('min' => $this->getParameter()->getMin(), 'max' => $this->getParameter()->getMax()));
	}
}

/**
 * Base for "(min|max)Size" validators
 */
abstract class validation_AbstractSizeValidator extends validation_ValidatorImpl implements validation_Validator
{
	/**
	 * @var String
	 */
	private $fieldType;

	/**
	 * Validate $data and append error message in $errors.
	 *
	 * @param validation_Property $Field
	 * @param validation_Errors $errors
	 *
	 * @return void
	 */
	protected function doValidate(validation_Property $field, validation_Errors $errors)
	{
		$size = $this->getParameter();
		$value = $field->getValue();
		$type = $field->getType();

		if ($type === null)
		{
			// TODO: remove this ugly thing that changes the expected type using the type of the given value !!
			if ($value === null)
			{
				$len = 0;
			}
			else if (is_string($value))
			{
				$len = f_util_StringUtils::strlen($value);
			}
			else if (is_numeric($value))
			{
				$value = strval($value);
				$len = f_util_StringUtils::strlen($value);
			}
			else if (is_array($value))
			{
				$this->fieldType = "array";
				$len = count($value);
			}
			else if ($value instanceof ArrayObject)
			{
				$this->fieldType = "array";
				$len = $value->count();
			}
			else
			{
				throw new IllegalArgumentException("Value of field \"".$field->getName()."\" must be a string or an array");
			}
		}
		elseif ($type !== null)
		{
			switch ($type)
			{
				case BeanPropertyType::DOCUMENT:
				case BeanPropertyType::CLASS_TYPE:
				case BeanPropertyType::BEAN:
					$this->fieldType = "array";
					break;
				default:
					$this->fieldType = null;
			}
			if ($this->fieldType === null)
			{
				if ($value === null)
				{
					$len = 0;
				}
				else if (!is_string($value))
				{
					throw new Exception("String expected for property ".$field->getName());
				}
				$len = strlen($value);
			}
			elseif ($this->fieldType == "array")
			{
				if ($value === null)
				{
					$len = 0;
				}
				else if (is_array($value))
				{
					$len = count($value);
				}
				else if ($value instanceof ArrayObject)
				{
					$len = $value->count();
				}
				else
				{
					throw new Exception("Array expected for property ".$field->getName());
				}
			}
			else
			{
				throw new Exception("Unknown field type ".$this->fieldType);
			}
		}

		if (!$this->isValidLen($len, $size))
		{
			$this->reject($field->getName(), $errors);
		}
	}
	
	/**
	 * @param integer $len
	 * @param integer $size
	 * @return boolean
	 */
	abstract protected function isValidLen($len, $size);

	protected function getMessageCode()
	{
		if ($this->fieldType === null)
		{
			return parent::getMessageCode();
		}
		$key = 'f.validation.validator.'.substr(get_class($this), 11, -9).'-'.$this->fieldType.'.message';
		if ($this->usesReverseMode)
		{
			$key .= '.reversed';
		}
		return $key;
	}
}

class validation_MaxSizeValidator extends validation_AbstractSizeValidator
{
	/**
	 * @param integer $len
	 * @param integer $maxSize
	 * @return boolean
	 */
	protected function isValidLen($len, $maxSize)
	{
		return ($len <= $maxSize);
	}
	
	public function setParameter($value)
	{
		$value = intval($value);
		if ($value <= 0 )
		{
			throw new ValidatorConfigurationException('Must be an positive integer greater than 0');
		}
		parent::setParameter($value);
	}
}

class validation_MinSizeValidator extends validation_AbstractSizeValidator
{
	/**
	 * @param integer $len
	 * @param integer $size
	 * @return boolean
	 */
	protected function isValidLen($len, $size)
	{
		return ($len >= $size);
	}
	
	public function setParameter($value)
	{
		$value = intval($value);
		if ($value < 0)
		{
			throw new ValidatorConfigurationException('Must be an positive integer');
		}
		parent::setParameter($value);
	}
}
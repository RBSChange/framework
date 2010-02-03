<?php
/**
 * @package framework.validation
 */
class validation_IntegerValidator extends validation_ValidatorImpl implements validation_Validator
{
	public function __construct()
	{
		$this->setParameter(true);
	}


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
		if ($this->getParameter() == true)
		{
			if ( ! is_int(f_util_Convert::fixDataType($this->stripZeros($field->getValue()))))
			{
				$this->reject($field->getName(), $errors);
			}
		}
	}


	/**
	 * Sets the value of the unique validator's parameter.
	 *
	 * @param mixed $value
	 */
	public function setParameter($value)
	{
		parent::setParameter(validation_BooleanValueParser::getValue($value));
	}
	
	/**
	 * @param String $value
	 * @return String
	 */
	private function stripZeros($value)
	{
		if (!is_string($value))
		{
			return $value;
		}
		$index = 0;
		$valueCount = strlen($value);
		if (isset($value[$index]))
		{
			while ($value[$index] == '0' && $index < $valueCount-1)
			{
				$index++;
			}
		}
		return substr($value, $index);
	}
}
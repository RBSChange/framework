<?php
class validation_BeginsWithValidator extends validation_ValidatorImpl implements validation_Validator
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
		$value = $field->getValue();
		if (is_numeric($value))
		{
			$value = strval($value);
		}
		if (!is_string($value))
		{
			throw new IllegalArgumentException("Field's value must be a string");
		}
		if ( ! f_util_StringUtils::beginsWith($value, $this->getParameter()) )
		{
			$this->reject($field->getName(), $errors);
		}
	}
	
	
	/**
	 * Sets the value of the unique validator's parameter.
	 *
	 * @param mixed $value
	 */
	public function setParameter($value)
	{
		parent::setParameter(validation_StringValueParser::getValue($value));
	}
}
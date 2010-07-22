<?php
/**
 * @package framework.validation
 */
class validation_MinValidator extends validation_ValidatorImpl implements validation_Validator
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
		$min = $this->getParameter();
		if ($field->getValue() < $min)
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
		parent::setParameter(validation_NumberValueParser::getValue($value));
	}
}
<?php
/**
 * @package framework.validation
 */
class validation_NotEqualValidator extends validation_ValidatorImpl implements validation_Validator
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
		if ($this->getParameter() == $field->getValue())
		{
			$this->reject($field->getName(), $errors);
		}
	}
}
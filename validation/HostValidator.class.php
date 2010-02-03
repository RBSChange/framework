<?php
/**
 * @package framework.validation
 */
class validation_HostValidator extends validation_ValidatorImpl implements validation_Validator
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
			$value = $field->getValue();
			if (!is_string($value))
			{
				throw new IllegalArgumentException("Field's value must be a string");
			}
			if (!preg_match('/^(https|http)\:\/\/([a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3})$/', $value) )
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
}
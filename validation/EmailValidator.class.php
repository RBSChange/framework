<?php
/**
 * @package framework.validation
 */
class validation_EmailValidator extends validation_ValidatorImpl implements validation_Validator
{
	const EMAIL_REGEXP = '/^[a-z0-9][a-z0-9_.-]*@[a-z0-9][a-z0-9.-]*\.[a-z]{2,}$/i';


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
			if ($value !== null && (!is_string($value) || ($value !== "" && !preg_match(self::EMAIL_REGEXP, $value))))
			{
				$this->reject($field->getName(), $errors);
			}
		}
	}


	/**
	 * Sets the value of the mail validator's parameter.
	 *
	 * @param mixed $value
	 */
	public function setParameter($value)
	{
		parent::setParameter(validation_BooleanValueParser::getValue($value));
	}
}
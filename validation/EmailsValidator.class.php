<?php
/**
 * @package framework.validation
 */
class validation_EmailsValidator extends validation_ValidatorImpl implements validation_Validator
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
			$addressArray = explode(',', $field->getValue());
			foreach ($addressArray as $address)
			{
				if (!is_string($address) || !preg_match(validation_EmailValidator::EMAIL_REGEXP, trim($address)))
				{
					$this->reject($field->getName(), $errors);
				}
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
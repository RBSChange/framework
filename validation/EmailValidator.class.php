<?php
/**
 * @package framework.validation
 */
class validation_EmailValidator extends validation_ValidatorImpl implements validation_Validator
{
	/**
	 * FIX #46269 - source http://atranchant.developpez.com/code/validation/
	 */
	const EMAIL_REGEXP = '/^[^\t,@]+@[^\s\t,@]{1,63}\.[a-z0-9]{2,10}$/i';


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
			if ($value !== null && $value !== '' && !self::isEmail($value))
			{
				$this->reject($field->getName(), $errors);
			}
		}
	}

	/**
	 * @param string $email
	 * @return boolean
	 */
	public static function isEmail($email)
	{
		if (!is_string($email) || $email === '' || strlen($email) > 255)
		{
			return false;
		}
		elseif (function_exists('filter_var'))
		{
			return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
		}
		return preg_match(self::EMAIL_REGEXP, $email);
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
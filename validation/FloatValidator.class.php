<?php
/**
 * @package framework.validation
 */
class validation_FloatValidator extends validation_ValidatorImpl implements validation_Validator
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
			if (!empty($value))
			{
				$regExp = LocaleService::getInstance()->trans('f.validation.validator.float.regexp');
				if ($regExp === 'f.validation.validator.float.regexp') 
				{
					$regExp = '^([\\-+]?)(\\d{0,8})?[\\.,]?(\\d{0,8})?$';
				}
				else
				{
					$regExp = str_replace('\\\\', '\\', $regExp);
				}
				if (!preg_match("/" . $regExp ."/i", $value))
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
<?php
/**
 * @package framework.validation
 */
class validation_UrlValidator extends validation_ValidatorImpl implements validation_Validator
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
			if (!f_util_StringUtils::isEmpty($value))
			{
				if (!is_string($value))
				{
					throw new IllegalArgumentException("Field's value must be a string");
				}
				// TODO intbonjf 2007-02-21: Maybe this regular expression has to be reviewed...
				if ( ! preg_match('/^[a-z]+:\/\/([a-z0-9\-\.]+\.[a-z0-9]+)|localhost(:[\d]{1,5})?(\/.*)?$/', $value) )
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
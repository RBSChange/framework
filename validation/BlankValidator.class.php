<?php
class validation_BlankValidator extends validation_ValidatorImpl implements validation_Validator
{
	public function __construct()
	{
		$this->setParameter(false);
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
		if ($this->getParameter() === false)
		{
			$value = $field->getValue();
			if (is_string($value))
			{
				if (strlen(trim($value)) == 0)
				{
					$this->reject($field->getName(), $errors);
				}
			}
			else if (is_array($value))
			{
				if (count($value) == 0)
				{
					$this->reject($field->getName(), $errors);
				}
			}
			else if ($value instanceof ArrayObject)
			{
				if ($value->count() == 0)
				{
					$this->reject($field->getName(), $errors);
				}
			}
			else if (is_null($value))
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
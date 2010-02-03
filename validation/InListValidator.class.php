<?php
/**
 * @package framework.validation
 */
class validation_InListValidator extends validation_ValidatorImpl implements validation_Validator
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
		if ( ! in_array($value, $this->getParameter()) )
		{
			$this->reject($field->getName(), $errors);
		}
	}
	
	
	public function setParameter($value)
	{
		if ( is_string($value) )
		{
			$value = validation_InListValueParser::getValue($value);
		}
		if ( ! is_array($value) )
		{
			throw new ValidatorConfigurationException('Must be an array');
		}
		parent::setParameter($value);
	}
	
	
	protected function getMessage()
	{
		return f_Locale::translate(
			$this->getMessageCode(),
			array('param' => join(', ', $this->getParameter()))
			);
	}
}
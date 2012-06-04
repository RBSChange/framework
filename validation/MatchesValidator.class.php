<?php
/**
 * @package framework.validation
 */
class validation_MatchesValidator extends validation_ValidatorImpl implements validation_Validator
{
	private $localizedErrorMessage;
	
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
		$regExp = $this->getParameter();
		// Extract a localized error message if needed
		if (($splitIndex = strpos($regExp, '#')) !== false)
		{
			$this->localizedErrorMessage = substr($regExp, $splitIndex + 1);
			$regExp = substr($regExp, 0, $splitIndex);
		}
		
		$value = $field->getValue();
		if (is_numeric($value))
		{
			$value = strval($value);
		}
		
		if (!is_string($value) || !@preg_match('#' . $regExp . '#', trim($value)))
		{
			$this->reject($field->getName(), $errors);
		}
	}
	
	protected function getMessage()
	{
		if ($this->localizedErrorMessage !== null)
		{
			return LocaleService::getInstance()->trans($this->localizedErrorMessage);
		}
		return parent::getMessage();
	}
}
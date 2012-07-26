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
	
	/**
	 * Returns the error message.
	 * @return string
	 */
	protected function getMessage($args = null)
	{
		if ($this->localizedErrorMessage !== null)
		{
			if ($this->localizedErrorMessage[0] != '&')
			{
				$this->localizedErrorMessage = '&' . $this->localizedErrorMessage;
			}
			if (f_Locale::isLocaleKey($this->localizedErrorMessage.';'))
			{
				return f_Locale::translate($this->localizedErrorMessage.';', array());
			}
		}
		return parent::getMessage($args);
	}
}
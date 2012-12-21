<?php
/**
 * @package framework.validation
 */
class validation_RegexpValidator extends validation_ValidatorImpl implements validation_Validator
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
		
		$firstChar = substr($regExp, 0, 1);
		$regExpExtractor = "\\" . $firstChar . "(.*)\\" . $firstChar . "(.*)";
		
		$captures = array();
		preg_match('/' . $regExpExtractor . '/', $regExp, $captures);
		
		if (isset($captures[2]) && f_util_StringUtils::isNotEmpty($captures[2]))
		{
			$this->localizedErrorMessage = $captures[2];
		}
		
		$value = $field->getValue();
		if (is_numeric($value))
		{
			$value = strval($value);
		}
		
		if (!is_string($value) || preg_match($firstChar . $captures[1] . $firstChar, trim($value)) == 0)
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
			return LocaleService::getInstance()->transFO($this->localizedErrorMessage);
		}
		return parent::getMessage($args);
	}
}
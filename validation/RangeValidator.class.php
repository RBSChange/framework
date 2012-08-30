<?php
/**
 * @package framework.validation
 */
class validation_RangeValidator extends validation_ValidatorImpl implements validation_Validator
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
		$range = $this->getParameter();
		$value = $field->getValue();
		
		$min = $range->getMin();
		$max = $range->getMax();
		if ($value < $min || $value > $max)
		{
			$this->reject($field->getName(), $errors);
		}
	}
	
	
	public function setParameter($value)
	{
		if ( is_integer($value) )
		{
			$value = new validation_Range($value, $value);
		}
		else
		{
			$value = validation_RangeValueParser::getValue($value);
		}
		parent::setParameter($value);
	}
	
	/**
	 * Returns the error message.
	 * @param array $args
	 * @return string
	 */
	protected function getMessage($args = null)
	{
		return LocaleService::getInstance()->trans($this->getMessageCode(), array(),
			array('min' => $this->getParameter()->getMin(), 'max' => $this->getParameter()->getMax()));
	}
}
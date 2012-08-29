<?php
class bean_DecimalConverter implements BeanValueConverter
{
	/**
	 * @param Mixed $value 
	 * @return Mixed 
	 * @see BeanValueConverter::convertFromBeanToRequestValue()
	 */
	public function convertFromBeanToRequestValue ($value)
	{
		return $value;		
	}
	
	/**
	 * 
	 * @param Mixed $value 
	 * @return Mixed 
	 * @see BeanValueConverter::convertFromRequestToBeanValue()
	 */
	public function convertFromRequestToBeanValue ($value)
	{
		return f_util_Convert::toFloat($value);
	}
	
	/**
	 * @see BeanValueConverter::isValidRequestValue()
	 *
	 * @param Mixed $value
	 * @return boolean
	 */
	public function isValidRequestValue($value)
	{
		return $value === null || is_numeric(str_replace(",", ".", $value));
	}
}
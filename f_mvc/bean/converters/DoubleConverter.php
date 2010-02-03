<?php
class bean_DoubleConverter
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
		return f_util_Convert::toDouble($value);
	}
	
	/**
	 * @see BeanValueConverter::isValidRequestValue()
	 *
	 * @param Mixed $value
	 * @return Boolean
	 */
	public function isValidRequestValue($value)
	{
		return null === $value || is_numeric(str_replace(",", ".", $value));
	}
}

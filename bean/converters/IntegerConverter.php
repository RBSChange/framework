<?php
class bean_IntegerConverter implements BeanValueConverter
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
	 * @param Mixed $value 
	 * @return Mixed 
	 * @see BeanValueConverter::convertFromRequestToBeanValue()
	 */
	public function convertFromRequestToBeanValue ($value)
	{
		return f_util_Convert::toInteger($value);
	}
	
	/**
	 * @see BeanValueConverter::isValidRequestValue()
	 *
	 * @param Mixed $value
	 * @return boolean
	 */
	public function isValidRequestValue($value)
	{
		return null === $value || preg_match('/^-{0,1}\d+$/', $value);
	}
}

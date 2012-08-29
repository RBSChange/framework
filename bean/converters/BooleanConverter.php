<?php

class bean_BooleanConverter implements BeanValueConverter
{
	
	/**
	 * 
	 * @param Mixed $value 
	 * @return Mixed 
	 * @see BeanValueConverter::convertFromBeanToRequestValue()
	 */
	public function convertFromBeanToRequestValue ($value)
	{
		if ($value === null)
		{
			return null;
		}
		
		if ($value)
		{
			return "true";
		}
		return "false";
	}
	
	/**
	 * 
	 * @param Mixed $value 
	 * @return Mixed 
	 * @see BeanValueConverter::convertFromRequestToBeanValue()
	 */
	public function convertFromRequestToBeanValue ($value)
	{
		return f_util_Convert::toBoolean($value);
	}
	
	/**
	 * @see BeanValueConverter::isValidRequestValue()
	 *
	 * @param Mixed $value
	 * @return boolean
	 */
	public function isValidRequestValue($value)
	{
		return true;
	}
}
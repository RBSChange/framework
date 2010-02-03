<?php
interface BeanValueConverter
{
	
	/**
	 * @param Mixed $value
	 * @return Boolean
	 */
	public function isValidRequestValue($value);
	
	/**
	 * @param Mixed $value
	 * @return Mixed
	 */
	public function convertFromRequestToBeanValue($value);
	
	/**
	 * @param Mixed $value
	 * @return Mixed
	 */
	public function convertFromBeanToRequestValue($value);
}
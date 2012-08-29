<?php
interface BeanValueConverter
{
	
	/**
	 * @param Mixed $value
	 * @return boolean
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
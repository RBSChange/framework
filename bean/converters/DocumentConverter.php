<?php
class bean_DocumentConverter implements BeanValueConverter
{
	/**
	 * @see BeanValueConverter::convertFromBeanToRequestValue()
	 *
	 * @param Mixed $value
	 * @return Mixed
	 */
	public function convertFromBeanToRequestValue($value)
	{
		if ($value instanceof f_persistentdocument_PersistentDocument)
		{
			return $value->getId();
		}
		return null;
	}
	
	/**
	 * @see BeanValueConverter::convertFromRequestToBeanValue()
	 *
	 * @param Mixed $value
	 * @return Mixed
	 */
	public function convertFromRequestToBeanValue($value)
	{
		return f_util_Convert::toDocument($value);
	}
	
	/**
	 * @see BeanValueConverter::isValidRequestValue()
	 *
	 * @param Mixed $value
	 * @return Boolean
	 */
	public function isValidRequestValue($value)
	{
		return $value === null || is_numeric($value) || $value instanceof f_persistentdocument_PersistentDocument;
	}
}
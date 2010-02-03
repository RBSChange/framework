<?php
class bean_DocumentsConverter implements BeanValueConverter
{
	/**
	 * @see BeanValueConverter::convertFromBeanToRequestValue()
	 *
	 * @param Mixed $value
	 * @return Mixed
	 */
	public function convertFromBeanToRequestValue($value)
	{
		if (is_array($value))
		{
			return DocumentHelper::getIdArrayFromDocumentArray($value);
		}
		return null;
	}

	/**
	 * @see BeanValueConverter::convertFromRequestToBeanValue()
	 *
	 * @param Mixed $value
	 * @return Mixed
	 */
	public function convertFromRequestToBeanValue($values)
	{
		$documents = array();
		if (!is_array($values))
		{
			if (f_util_StringUtils::isNotEmpty($values))
			{
				$values = explode(",", $values);
			}
			else
			{
				return $documents;
			}
		}
		foreach ($values as $value)
		{
			$documents[] = f_util_Convert::toDocument($value);
		}
		return $documents;
	}

	/**
	 * @see BeanValueConverter::isValidRequestValue()
	 *
	 * @param Mixed $value
	 * @return Boolean
	 */
	public function isValidRequestValue($value)
	{
		return is_array($value) || f_util_StringUtils::isEmpty($value) || preg_match('/^(\d+)(,(\d+))*$/', $value);
	}
}
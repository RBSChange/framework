<?php
class bean_XHTMLFragmentConverter implements BeanValueConverter
{
	
	/**
	 * @see BeanValueConverter::convertFromBeanToRequestValue()
	 *
	 * @param Mixed $value
	 * @return Mixed
	 */
	public function convertFromBeanToRequestValue($value)
	{
		return $value;
	}
	
	/**
	 * @see BeanValueConverter::convertFromRequestToBeanValue()
	 *
	 * @param Mixed $value
	 * @return Mixed
	 */
	public function convertFromRequestToBeanValue($value)
	{
		$formatter = new formatter_Xhtml();
		$formatted = $formatter->format($value);
		return website_XHTMLCleanerHelper::clean(f_util_HtmlUtils::htmlEntitiesToXMLEntities($formatted));
	}
	
	/**
	 * @see BeanValueConverter::isValidRequestValue()
	 *
	 * @param Mixed $value
	 * @return Boolean
	 */
	public function isValidRequestValue($value)
	{
		return true;
	}
}
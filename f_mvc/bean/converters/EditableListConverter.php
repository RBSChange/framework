<?php
class bean_EditableListConverter implements BeanValueConverter
{
	/**
	 * @var list_persistentdocument_editablelist
	 */
	private $editableList;
	
	public function __construct($editableList)
	{
		$this->editableList = $editableList;
	}
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
		if (is_numeric($value))
		{
			return DocumentHelper::getDocumentInstance($value);
		}
		return $value;
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
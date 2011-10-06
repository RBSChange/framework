<?php
class bean_EditableListConverter implements BeanValueConverter
{
	/**
	 * @var list_persistentdocument_editablelist
	 */
	private $editableList;
	
	/**
	 * @param list_persistentdocument_editablelist $editableList
	 */
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
		if ($value instanceof list_persistentdocument_valueditem)
		{
			return $value->getValue();
		}
		if ($value instanceof list_persistentdocument_item)
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
		if(is_array($value))
		{
			$do = 'in';
			$method = 'find';
		}
		else
		{
			$do = 'eq';
			$method = 'findUnique';
		}
		
		if ($this->editableList instanceof list_persistentdocument_valuededitablelist)
		{
			return list_ValueditemService::getInstance()->createQuery()
				->add(Restrictions::eq("valuededitablelist", $this->editableList))
				->add(Restrictions::$do("value", $value))->$method();
		}
		if ($this->editableList instanceof list_persistentdocument_editablelist)
		{
			return list_ItemService::getInstance()->createQuery()
				->add(Restrictions::eq("editablelist", $this->editableList))
				->add(Restrictions::$do("id", $value))->$method();
		}
		
		return null;
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
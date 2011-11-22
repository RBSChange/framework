<?php
class bean_EditableListMultipleConverter implements BeanValueConverter
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
		if (is_array($value))
		{
			$values = array();
			foreach ($value as $oneValue)
			{
				if ($oneValue instanceof list_persistentdocument_valueditem)
				{
					$values[] = $oneValue->getValue();
				}
				if ($oneValue instanceof list_persistentdocument_item)
				{
					$values[] = $oneValue->getId();
				}
			}
			return $values;
		}
		return null;
	}
	
	/**
	 * @see BeanValueConverter::convertFromRequestToBeanValue()
	 *
	 * @param Mixed $values
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
			$item = null;
			if ($this->editableList instanceof list_persistentdocument_valuededitablelist)
			{
				if ($value instanceof list_persistentdocument_valueditem) { $value = $value->getValue(); }
				elseif (is_object($value)) { continue; }
				$item = list_ValueditemService::getInstance()->createQuery()
					->add(Restrictions::eq("valuededitablelist", $this->editableList))
					->add(Restrictions::eq("value", $value))->findUnique();
			}
			elseif ($this->editableList instanceof list_persistentdocument_editablelist)
			{
				if ($value instanceof list_persistentdocument_item) { $value = $value->getId(); }
				elseif (!is_numeric($value)) { continue; }
				$item = list_ItemService::getInstance()->createQuery()
					->add(Restrictions::eq("editablelist", $this->editableList))
					->add(Restrictions::eq("id", $value))->findUnique();
			}
			
			if ($item)
			{
				$documents[] = $item;
			}
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
		return true;
	}
}
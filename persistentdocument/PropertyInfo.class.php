<?php
/**
 * @package framework.persistentdocument
 */
class PropertyInfo extends \Change\Documents\Property
{
	/**
	 * @deprecated with no replacement
	 */
	public function isPrimaryKey()
	{
		return false;
	}
	
	/**
	 * @deprecated with no replacement
	 */
	public function hasSpecificIndex()
	{
		return false;
	}
	
	/**
	 * @deprecated use getTreeNode
	 */
	public function isTreeNode()
	{
		return $this->treeNode;
	}
	
	/**
	 * @deprecated use getCascadeDelete
	 */
	public function isCascadeDelete()
	{
		return $this->getCascadeDelete();
	}
	
	/**
	 * @deprecated use getLocalized
	 */
	public function isLocalized()
	{
		return $this->getLocalized();
	}
	
	/**
	 * @deprecated use getPersistentModel
	 */
	public function getDocumentModel()
	{
		return $this->getPersistentModel();
	}
	
	/**
	 * @deprecated
	 */
	public function getConstraints()
	{
		$constraintArray = $this->getConstraintArray();
		if (is_array($constraintArray))
		{
			$const = array();
			if ($this->isRequired())
			{
				$const[] = 'blank:false';
			}
			foreach ($constraintArray as $name => $params) 
			{
				if (isset($params['reversed'])) 
				{
					$name = '!' . $name;
					unset($params['reversed']);
				}
				if (isset($params['parameter']))
				{
					$const[] = $name . ':' . $params['parameter'];
				}
				elseif (count($params))
				{
					$const[] = $name . ':' . f_util_ArrayUtils::firstElement($params);
				}
				else
				{
					$const[] = $name . ':true';
				}
			}
			return count($const) ? implode(';', $const) : null;
		}
		return $constraintArray;
	}
	
	/**
	 * @deprecated with no replacement
	 */
	public function setConstraints($constraints)
	{
		if ($constraints !== null && is_string($constraints))
		{
			$cp = new validation_ContraintsParser();
			$defs = $cp->getConstraintArrayFromDefinition($constraints);
			$constraints = null;
			foreach ($defs as $name => $parameter)
			{
				$params = array('parameter' => $parameter);
				if ($constraints === null) {$constraints = array();}
				if ($name{0} === '!')
				{
					$name = substr($name, 1);
					$params['reversed'] = true;
				}
				$constraints[$name] = $params;
			}
		}
		return $this->setConstraintArray($constraints);
	}
}
<?php
/**
 * @package framework.persistentdocument
 */
abstract class f_persistentdocument_PersistentDocument extends \Change\Documents\AbstractDocument implements f_mvc_Bean
{
	/**
	 * @deprecated
	 */
	const PROPERTYTYPE_STRING_DEAFULT_MAX_LENGTH = 255;

	/**
	 * @deprecated
	 */
	public function getBeanId()
	{
		return $this->getId();
	}
	
	/**
	 * @deprecated
	 */
	function getBeanModel()
	{
		return $this->getPersistentModel();
	}
	
	/**
	* @deprecated
	*/
	public function __get($property)
	{
		if ($property === 'validationErrors')
		{
			Framework::deprecated('Call to deleted ' . get_class($this) . '->' . $property . ' property');
			$v = new validation_Errors();
			$v->setDocument($this);
			return $v;
		}
		return null;
	}
	
	
	/**
	 * @deprecated
	 */
	public function __call($name, $args)
	{
		switch ($name)
		{
			case 'addValidationError':
				Framework::deprecated('Call to deleted ' . get_class($this) . '->' . $name . ' method');
				$this->addPropertyErrors('unknow', $args[0]);
				return;
			case 'getValidationErrors':
				Framework::deprecated('Call to deleted ' . get_class($this) . '->' . $name . ' method');
				$v = new validation_Errors();
				$v->setDocument($this);
				return $v;
			default:
				throw new BadMethodCallException('No method ' . get_class($this) . '->' . $name);
		}
	}
}
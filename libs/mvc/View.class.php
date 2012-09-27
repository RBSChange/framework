<?php
/**
 * @deprecated use \Change\Mvc\AbstractView
 */
abstract class change_View extends \Change\Mvc\AbstractView
{
	/**
	 * @deprecated
	 */
	public function setAttributeByRef($name, &$value)
	{
		$this->attributes[$name] = $value;
	}
	
	/**
	 * @deprecated
	 */
	public function setAttributesByRef(&$attributes)
	{
		foreach ($attributes as $name => $value)
		{
			$this->setAttributeByRef($name, $value);
		}
	}
	
	/**
	 * @deprecated
	 */
	protected function getModuleName()
	{
		return $this->moduleName;
	}
	
	/**
	 * @deprecated
	 */
	public final function getDocumentService()
	{
		return f_persistentdocument_DocumentService::getInstance();
	}
	
	/**
	 * @deprecated
	 */
	protected final function forceModuleName($moduleName)
	{
		$this->moduleName = $moduleName;
	}
}
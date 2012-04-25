<?php
/**
 * @deprecated
 */
class export_ExportedDocument
{	
	private $properties = array();
	private $lang;

	public function setLang($lang)
	{
		$this->lang = $lang;
	}
	
	public function getLang()
	{
		return $this->lang;
	}
	
	public function setProperty($name, $value)
	{
		$this->properties[0][$name] = $value;
	}
		
	public function getProperties()
	{
		return $this->properties;
	}
	
	public function addChildProperties($array)
	{
		foreach ($array as $a)
		{
			$this->properties[] = $a;
		}
	}
	
}



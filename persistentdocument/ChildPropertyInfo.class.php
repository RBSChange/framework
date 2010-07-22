<?php
/**
 * @package framework.persistentdocument
 */
class ChildPropertyInfo
{
	private $name;
	private $type;
	
	/**
	 * Constructor of ChildPropertyInfo
	 * @param string $name
	 * @param string $type
	 */
	function __construct($name, $type)
	{
		$this->name = $name;
		$this->type = $type;
	}
	
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
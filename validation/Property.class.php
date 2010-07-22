<?php
/**
 * @package framework.validation
 */
class validation_Property
{
	private $name;
	private $value;
	private $type;
	
	public function __construct($name, $value, $type = null)
	{
		$this->name  = $name;
		$this->value = $value;
		$this->type = $type;
	}
	
	
	public function getName()
	{
		return $this->name;
	}
	
	
	public function getValue()
	{
		return $this->value;
	}
	
	public function getType()
	{
		return $this->type;
	}
}
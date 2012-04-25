<?php
/**
 * @package framework.persistentdocument
 */
/**
 * @deprecated
 */
class FormPropertyInfo
{
	private $name;
	private $controlType;
	private $display;
	private $required;
	private $label;
	private $attributes;

	/**
	 * Constructor of FormPropertyInfo
	 *
	 * @param String $name
	 * @param String $controlType
	 * @param String $display
	 * @param Boolean $required
	 * @param String $label
	 * @param String $attributes
	 */
	function __construct($name, $controlType, $display, $required, $label, $attributes)
	{
		$this->name = $name;
		$this->controlType = $controlType;
		$this->display = $display;
		$this->required = $required;
		$this->label = $label;
		if (is_string($attributes))
		{
			$this->attributes = unserialize($attributes);
		}
		else if (is_array($attributes))
		{
			$this->attributes = $attributes;
		}
		else
		{
			$this->attributes = array();
		}
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
    public function getControlType()
    {
        return $this->controlType;
    }

    /**
     * @return boolean
     */
    public function isHidden()
    {
        return 'hidden' == $this->display;
    }

    /**
     * @return boolean
     */
    public function isReadonly()
    {
        return 'readonly' == $this->display;
    }

    /**
     * @return boolean
     */
    public function isEditOnce()
    {
        return 'editonce' == $this->display;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }


    public function getLabel()
    {
    	return $this->label;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}
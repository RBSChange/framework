<?php
/**
 * Auto-generated doc comment
 * @package framework.object
 */
/**
 * @date Tue Mar 27 09:46:35 CEST 2007
 * @author INTbonjF
 */
class object_TagObject
{
	private $value		= null;
	private $label		= null;
	private $icon		 = null;
	private $package	  = null;
	private $packageIcon  = null;
	private $packageLabel = null;
	private $affected	 = null;


	/**
	 * @param string $value
	 * @param string $label
	 * @param string $icon
	 * @param string $package
	 * @param string $packageLabel
	 */
	public function __construct($value, $label, $icon = null, $package = null, $packageLabel = null)
	{
		$this->value		= $value;
		$this->label		= $label;
		$this->icon		 = $icon;
		$this->package	  = $package;
		$this->packageLabel = $packageLabel;
	}


	/**
	 * @param string $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param string $label
	 */
	public function setLabel($label)
	{
		$this->label = $label;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @param string $icon
	 */
	public function setIcon($icon)
	{
		$this->icon = $icon;
	}

	/**
	 * @return string
	 */
	public function getIcon()
	{
		return $this->icon;
	}

	/**
	 * @param string $package
	 */
	public function setPackage($package)
	{
		$this->package = $package;
	}

	/**
	 * @return string
	 */
	public function getPackage()
	{
		return $this->package;
	}

	/**
	 * @param string $packageLabel
	 */
	public function setPackageLabel($packageLabel)
	{
		$this->packageLabel = $packageLabel;
	}

	/**
	 * @return string
	 */
	public function getPackageLabel()
	{
		return $this->packageLabel;
	}

	/**
	 * @param string $packageIcon
	 */
	public function setPackageIcon($packageIcon)
	{
		$this->packageIcon = $packageIcon;
	}

	/**
	 * @return string
	 */
	public function getPackageIcon()
	{
		return $this->packageIcon;
	}


	public function __toString()
	{
		return $this->value;
	}


	public function setAffected($bool)
	{
		$this->affected = ($bool === true);
	}

	public function isAffected()
	{
		return $this->affected;
	}
}
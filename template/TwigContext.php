<?php
class Twig_Context extends PHPTAL_Context
{
	protected $context = [];

	public function __set($varname, $value)
	{
		$this->context[$varname] = $value;
	}

	public function __get($varname)
	{
		return isset($this->context[$varname]) ? $this->context[$varname] : null;
	}

	public function getContext()
	{
		return $this->context;
	}

	/**
	 * Set output document xml declaration.
	 *
	 * This method ensure PHPTAL uses the first xml declaration encountered
	 * (main template or any macro template source containing an xml
	 * declaration).
	 */
	public function setXmlDeclaration($xmldec)
	{
	}
}

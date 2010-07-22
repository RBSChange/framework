<?php
interface f_web_CSSVariables
{
	/**
	 * @param string $name
	 * @param string $defaultValue
	 * @return string | null
	 */
	function getCSSValue($name, $defaultValue = '');
	
	/**
	 * Return a identifier for the set of variable
	 * @return string
	 */
	function getIdentifier();
}
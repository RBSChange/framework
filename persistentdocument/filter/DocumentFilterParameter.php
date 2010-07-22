<?php
/**
 * @author intportg
 * @package framework.persistentdocument.filter
 */
abstract class f_persistentdocument_DocumentFilterParameter
{
	/**
	 * @return Mixed
	 */
	abstract public function getValueForQuery();

	/**
	 * @return String
	 */
	abstract public function getValueAsText();

	/**
	 * @return String
	 */
	abstract public function getValueForXul();

	/**
	 * @return String
	 */
	abstract public function getValueForJson();
}
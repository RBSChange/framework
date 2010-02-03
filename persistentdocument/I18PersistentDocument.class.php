<?php
/**
 * Auto-generated doc comment
 * @package framework.persistentdocument
 */
interface f_persistentdocument_I18nPersistentDocument
{
	/**
	 * The document id
	 * @return Integer
	 */
	function getId();
	
	/**
	 * The document lang
	 * @return String
	 */
	function getLang();

	/**
	 * @return Boolean
	 */
	function isModified();
	
	/**
	 * @return String
	 */
	function getLabel();
	
	
	function setDefaultValues();
	
	/**
	 * @param String $propertyName
	 * @return Boolean
	 */
	function isPropertyModified($propertyName);
}
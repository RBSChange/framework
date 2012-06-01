<?php
/**
 * Auto-generated doc comment
 * @package framework.persistentdocument
 */
interface f_persistentdocument_I18nPersistentDocument
{
	/**
	 * The document id
	 * @return integer
	 */
	function getId();
	
	/**
	 * The document lang
	 * @return string
	 */
	function getLang();

	/**
	 * @return boolean
	 */
	function isModified();
	
	/**
	 * @return string
	 */
	function getLabel();
	
	/**
	 * @return string
	 */
	function getPublicationstatus();
	
	/**
	 * @return void
	 */
	function setDefaultValues();
	
	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	function isPropertyModified($propertyName);
}
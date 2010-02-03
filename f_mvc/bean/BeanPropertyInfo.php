<?php
class BeanPropertyType
{
	const BOOLEAN = 'Boolean';
	const INTEGER = 'Integer';
	const DOUBLE = 'Double';
	const DATETIME = 'DateTime';
	const DATE = 'Date';
	const STRING = 'String';
	const LOB = 'Lob';
	const LONGSTRING = 'LongString';
	const XHTMLFRAGMENT = 'XHTMLFragment';
	const DOCUMENT = 'Document';
	const BEAN = 'Bean';
	const CLASS_TYPE = 'Class';
}

interface BeanPropertyInfo
{
	/**
	 * @return String
	 */
	public function getName();
	/**
	 * @return String
	 */
	public function getType();
	
	/**
	 * If the property type is BeanPropertyType::DOCUMENT,
	 * returns the linked document model
	 * @return String
	 */
	public function getDocumentType();
	
	/**
	 * If the property type if DOCUMENT, BEAN or CLASS
	 * @return String
	 */
	public function getClassName();
	
	/**
	 * @return String
	 */
	public function getValidationRules();
	
	/**
	 * @return mixed
	 */
	public function getDefaultValue();
	
	/**
	 * @return String
	 */
	public function getLabelKey();
	
	/**
	 * @return String
	 */
	public function getHelpKey();
	
	/**
	 * @return Integer >=1 | -1, meaning "infinite"
	 */
	public function getCardinality();
	
	/**
	 * @return Boolean
	 */
	public function isRequired();
	
	/**
	 * @return Boolean
	 */
	public function isHidden();
	
	/**
	 *	@return BeanValueConverter or null
	 */
	public function getConverter();
	
	/**
	 * @return Boolean
	 */
	public function hasList();
	
	/**
	 * TODO: interface for list
	 * @return list_persistentdocument_list
	 */
	public function getList();
	
	/**
	 * @return Integer
	 */
	public function getMaxOccurs();
	
	/**
	 * @return Integer
	 */
	public function getMinOccurs();
	
	/**
	 * @return String
	 */
	public function getSetterName();
	
	/**
	 * @return String
	 */
	public function getGetterName();
}
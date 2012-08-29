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
	 * @return string
	 */
	public function getName();
	/**
	 * @return string
	 */
	public function getType();
	
	/**
	 * If the property type is BeanPropertyType::DOCUMENT,
	 * returns the linked document model
	 * @return string
	 */
	public function getDocumentType();
	
	/**
	 * If the property type if DOCUMENT, BEAN or CLASS
	 * @return string
	 */
	public function getClassName();
	
	/**
	 * @return string
	 */
	public function getValidationRules();
	
	/**
	 * @return mixed
	 */
	public function getDefaultValue();
	
	/**
	 * @return string
	 */
	public function getLabelKey();
	
	/**
	 * @return string
	 */
	public function getHelpKey();
	
	/**
	 * @return integer >=1 | -1, meaning "infinite"
	 */
	public function getCardinality();
	
	/**
	 * @return boolean
	 */
	public function isRequired();
	
	/**
	 * @return boolean
	 */
	public function isHidden();
	
	/**
	 *	@return BeanValueConverter or null
	 */
	public function getConverter();
	
	/**
	 * @return boolean
	 */
	public function hasList();
	
	/**
	 * TODO: interface for list
	 * @return list_persistentdocument_list
	 */
	public function getList();
	
	/**
	 * @return integer
	 */
	public function getMaxOccurs();
	
	/**
	 * @return integer
	 */
	public function getMinOccurs();
	
	/**
	 * @return string
	 */
	public function getSetterName();
	
	/**
	 * @return string
	 */
	public function getGetterName();
}
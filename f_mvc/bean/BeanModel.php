<?php
interface f_mvc_BeanModel
{	
	/**
	 * @return array<String, BeanPropertyInfo> ie. <propName, BeanpropertyInfo> 
	 */
	function getBeanPropertiesInfos();
	
	/**
	 * @param string $propertyName
	 * @return BeanPropertyInfo
	 * @throws Exception if property $propertyName does not exists
	 */
	function getBeanPropertyInfo($propertyName);

	/**
	 * @param string $propertyName
	 * @return boolean
	 */
	function hasBeanProperty($propertyName);
	
	/**
	 * Used as getter/setter prefix in the context of BeanAggregateModel
	 * @see BeanAggregateModel 
	 * @return String
	 */
	function getBeanName();
	
	/**
	 * @return String
	 */
	function getBeanConstraints();
}
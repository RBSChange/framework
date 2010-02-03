<?php
interface f_mvc_Bean
{
	/**
	 * @return f_mvc_BeanModel
	 */
	function getBeanModel();
	
	/**
	 * @param Mixed $id
	 * @return f_mvc_Bean
	 */
	static function getInstanceById($id);
	
	/**
	 * @return f_mvc_Bean
	 */
	static function getNewInstance();
	
	/**
	 * @return Mixed
	 */
	function getBeanId();
}
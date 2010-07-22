<?php
interface f_permission_ACL
{
	/**
	 * @return Integer
	 */
	function getAccessorId();
	
	/**
	 * @return String
	 */
	function getRole();
	
	/**
	 * @return Integer
	 */
	function getDocumentId();
}
?>
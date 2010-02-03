<?php
interface f_permission_RoleService
{
	/**
	 * returns the list of permissions attributed to each roles defined for 
	 * the module in config/roles.xml.
	 * 
	 * @param String $roleName full role name
	 * @return Array<String>
	 * @throws IllegalArgumentException
	 */
	function getPermissionsByRole($roleName);
	
	/**
	 * Returns the list of roles
	 * 
	 * @return Array<String>
	 *
	 */
	function getRoles();
	
		
	/**
	 * returns the list of permissions attributed to each roles defined for 
	 * the module in config/roles.xml.
	 * 
	 * @param String $roleName
	 * @return String
	 */
	function getRoleLabelKey($roleName);
	
	/**
	 * returns the compiled list of actions defined for the module in config/rights.xml
	 * 
	 * @return Array<String>
	 */
	function getActions();
	
	/**
	 * returns the compiled list of back office actions defined for the module in config/actions.xml
	 * 
	 * @return Array<String>
	 */
	function getBackOfficeActions();
	
	/**
	 * returns the list of permissions attributed to each roles defined for 
	 * the module in config/right.xml.
	 * 
	 * @param String $roleName
	 * @return Array<String>
	 * @throws IllegalArgumentException
	 */
	function getPermissionsByAction($actionName);
	
	/**
	 * @param String $roleName
	 * @return String
	 */
	function getBackOfficeActionName($actionName);
	
	/**
	 * @return array<String>
	 */
	function getPermissions();
	
	/**
	 * @param String $permissionName
	 * @return Boolean
	 */
	function hasPermission($permissionName);
	
	/**
	 * @param String $permissionName
	 * @return Boolean
	 */
	function isFrontEndPermission($permissionName);
	
	/**
	 * @param String $actionName
	 * @return Boolean
	 */
	function hasAction($actionName);
	
	/**
	 * @param array<String> $permissions
	 * @return array<String>
	 */
	function getActionsByPermissions($permissions);
	
	/**
	 * @param String $roleName
	 * @return Boolean
	 */
	function isFrontEndRole($roleName);
	
	/**
	 * @param String $roleName
	 * @return Boolean
	 */
	function isBackEndRole($roleName);
}
?>
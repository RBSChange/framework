<?php
interface change_RoleService
{
	/**
	 * Returns the list of roles
	 * @return string[]
	 */
	function getRoles();
	
	/**
	 * replace isBackEndRole isFrontEndRole
	 * @param string $roleName
	 * @return boolean
	 */
	function hasRole($roleName);	

	/**
	 * returns the compiled list of actions defined for the module in config/rights.xml
	 * 
	 * @return string[]
	 */
	function getActions();
	
	
	/**
	 * @param string $actionName
	 * @return boolean
	 */
	function hasAction($actionName);

	/**
	 * @return string[]
	 */
	function getPermissions();
	
	/**
	 * @param string $permissionName
	 * @return boolean
	 */
	function hasPermission($permissionName);

	
	/**
	 * returns the list of permissions attributed to each roles defined for 
	 * the module in config/roles.xml.
	 * 
	 * @param string $roleName full role name
	 * @return string[]
	 */
	function getPermissionsByRole($roleName);
		
	/**
	 * returns the list of permissions attributed to each roles defined for 
	 * the module in config/roles.xml.
	 * 
	 * @param string $roleName
	 * @return string
	 */
	function getRoleLabelKey($roleName);
	
	/**
	 * returns the list of permissions attributed to each roles defined for 
	 * the module in config/right.xml.
	 * 
	 * @param string $roleName
	 * @return string[]
	 */
	function getPermissionsByAction($actionName);
		
	/**
	 * @param string[] $permissions
	 * @return string[]
	 */
	function getActionsByPermissions($permissions);
	
}
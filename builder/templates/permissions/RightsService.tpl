<?php
class roles_<{$module->name|capitalize}>RoleService implements change_RoleService
{
	private static $instance = null;
	private static $moduleName = '<{$module->name}>';

	private static $roleNames = array(<{foreach from=$module->getRoles() item=role name=roles}>'<{$module->getPrefix()}><{$role->getName()}>'<{if !$smarty.foreach.roles.last}>,<{/if}><{/foreach}>);

	private static $actionNames = array(<{foreach from=$module->getActions() item=action name=actions}>'<{$module->getPrefix()}><{$action->getFullName()}>'<{if !$smarty.foreach.actions.last}>,<{/if}><{/foreach}>);

	private static $permissions = array(<{foreach from=$module->getPermissions() item=permission name=permissions}>'<{$module->getPrefix()}><{$permission->getName()}>'<{if !$smarty.foreach.permissions.last}>,<{/if}><{/foreach}>);

	private static $frontEndPermissions = array(<{foreach from=$module->getFrontEndPermissions() item=permission name=permissions}>'<{$module->getPrefix()}><{$permission->getName()}>'<{if !$smarty.foreach.permissions.last}>,<{/if}><{/foreach}>);

	private static $backOfficeActions = array(<{foreach from=$module->backOfficeActions item=action name=actions}>'<{$action}>'<{if !$smarty.foreach.actions.last}>,<{/if}><{/foreach}>);

	/**
	 * @return roles_<{$module->name|capitalize}>RoleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new roles_<{$module->name|capitalize}>RoleService();
		}
		return self::$instance;
	}

	/**
	 * returns the compiled list of roles defined for the module in config/roles.xml
	 *
	 * @return Array<String>
	 */
	public final function getRoles()
	{
		return self::$roleNames;
	}

	/**
	 * returns the compiled list of actions defined for the module in config/actions.xml
	 *
	 * @return Array<String>
	 */
	public final function getActions()
	{
		return self::$actionNames;
	}

	/**
	 * returns the compiled list of back office actions defined for the module in config/actions.xml
	 *
	 * @return Array<String>
	 */
	public final function getBackOfficeActions()
	{
		return self::$backOfficeActions;
	}

	/**
	 * returns the list of permissions attributed to each roles defined for
	 * the module in config/roles.xml.
	 *
	 * @param String $roleName
	 * @return Array<String>
	 * @throws IllegalArgumentException
	 */

	public final function getPermissionsByRole($roleName)
	{
		switch($roleName)
		{
			<{foreach from=$module->getRoles() item=role name=roles}>case '<{$module->getPrefix()}><{$role->getName()}>': return array(<{foreach from=$role->getPermissions() item=perm name=permissions}>'<{$module->getPrefix()}><{$perm->getName()}>'<{if !$smarty.foreach.permissions.last}>,<{/if}><{/foreach}>);
			<{/foreach}>
			default:
				throw new IllegalArgumentException('roleName','qualified role name');
		}
	}

	/**
	 * returns the list of permissions attributed to each roles defined for
	 * the module in config/roles.xml.
	 *
	 * @param String $roleName
	 * @return String
	 */
	public final function getRoleLabelKey($roleName)
	{
		return '&modules.<{$module->name}>.document.permission.' . ucfirst($roleName) . ';';
	}


	/**
	 * returns the list of permissions attributed to each roles defined for
	 * the module in config/right.xml.
	 *
	 * @param String $roleName
	 * @return Array<String>
	 * @throws IllegalArgumentException
	 */
	public final function getPermissionsByAction($actionName)
	{

		switch($actionName)
		{
			<{foreach from=$module->getActions() item=action name=actions}>case '<{$module->getPrefix()}><{$action->getFullName()}>': return array(<{foreach from=$action->getPermissions() item=perm name=permissions}>'<{$module->getPrefix()}><{$perm->getName()}>'<{if !$smarty.foreach.permissions.last}>,<{/if}><{/foreach}>);
			<{/foreach}>

			default:
				throw new IllegalArgumentException('actionName','qualified action name');
		}
	}

	/**
	 * @param String $roleName
	 * @return String
	 */
	public final function getBackOfficeActionName($actionName)
	{

		switch($actionName)
		{
			<{foreach from=$module->getActions() item=action name=actions}>case '<{$module->getPrefix()}><{$action->getFullName()}>': return '<{$action->getBackOfficeName()}>';
			<{/foreach}>
			default: return '';
		}
	}

	/**
	 * @param String $permissionName
	 * @return Boolean
	 */
	public final function hasPermission($permissionName)
	{
		return array_search($permissionName, self::$permissions) !== false;
	}

	/**
	 * @return array<String>
	 */
	public final function getPermissions()
	{
		return self::$permissions;
	}

	/**
	 * @param String $permissionName
	 * @return Boolean
	 */
	public final function isFrontEndPermission($permissionName)
	{
		return array_search($permissionName, self::$frontEndPermissions) !== false;
	}

	/**
	 * @param String $actionName
	 * @return Boolean
	 */
	public final function hasAction($actionName)
	{
		return array_search($actionName, self::$actionNames) !== false;
	}

	/**
	 * @param array<String> $permissions
	 * @return array<String>
	 */
	public final function getActionsByPermissions($permissions)
	{
		$result = array();
		foreach (self::$actionNames as $actionName)
		{
			$reqPermissions = $this->getPermissionsByAction($actionName);
			$perms = array_intersect($reqPermissions, $permissions);
			if (count($reqPermissions) == count($perms))
			{
				$result[] = $actionName;
			}
		}

		return $result;
	}
	
	public final function isFrontEndRole($roleName)
	{
		switch($roleName)
		{
			<{foreach from=$module->getRoles() item=role name=roles}>case '<{$module->getPrefix()}><{$role->getName()}>': return <{if $role->isFrontEnd()}>true<{else}>false<{/if}>;
			<{/foreach}>
			default:
				throw new IllegalArgumentException('roleName','qualified role name');
		}
	}
	
	public final function isBackEndRole($roleName)
	{
		switch($roleName)
		{
			<{foreach from=$module->getRoles() item=role name=roles}>case '<{$module->getPrefix()}><{$role->getName()}>': return <{if $role->isFrontEnd()}>false<{else}>true<{/if}>;
			<{/foreach}>
			default:
				throw new IllegalArgumentException('roleName','qualified role name');
		}
	}
}
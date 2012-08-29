<?php
/**
 * @method change_PermissionService getInstance()
 */
class change_PermissionService extends change_BaseService
{
	const ALL_PERMISSIONS = "allpermissions";

	/**
	 * @return users_UserService
	 */
	private function getUserService()
	{
		return users_UserService::getInstance();
	}

	/**
	 * @return users_GroupService
	 */
	private function getGroupService()
	{
		return users_GroupService::getInstance();
	}

	/**
	 * Get the RoleService instance handling the role $fullRoleName.
	 *
	 * @param string $fullRoleName
	 * @return change_RoleService
	 */
	public static function getRoleServiceByRole($fullRoleName)
	{
		return self::getRoleServiceByModuleName(self::getModuleNameByRole($fullRoleName));
	}
	
	/**
	 * Get the Modu instance handling the role $fullRoleName.
	 *
	 * @param string $fullRoleName
	 * @return change_RoleService
	 */
	public static function getModuleNameByRole($fullRoleName)
	{
		if (preg_match('/^modules_[\S]*\../', $fullRoleName) > 0)
		{
			$elems = preg_split('/[_.]/', $fullRoleName);
			return $elems[1];
		}
		else
		{
			throw new IllegalArgumentException($fullRoleName, "Valid Role Name");
		}
	}

	/**
	 * @param string $moduleName
	 * @return change_RoleService
	 */
	public static function getRoleServiceByModuleName($moduleName)
	{
		$className = 'roles_' . ucfirst($moduleName) . 'RoleService';
		if (f_util_ClassUtils::classExists($className))
		{
			return f_util_ClassUtils::callMethod($className, 'getInstance');
		}
		return null;
	}

	/**
	 * Try to resolve a role given a $roleName and $documentId.
	 * @param string $roleName
	 * @param integer $documentId
	 * @return string
	 */
	public static function resolveRole($roleName, $documentId = null)
	{
		$validRoleName = null;
		if (preg_match('/^modules_[\S]*\../', $roleName) > 0 || is_null($documentId))
		{
			$testRoleName = $roleName;
		}
		else
		{
			$documentModelName = DocumentHelper::getDocumentInstance($documentId)->getPersistentModel()->getName();
			list($longModuleName, ) = explode('/', $documentModelName);
			$testRoleName = $longModuleName . '.' . $roleName;
		}
		try
		{
			$service = self::getRoleServiceByRole($testRoleName);
			if (!is_null($service))
			{
				if (array_search($testRoleName, $service->getRoles()) !== false)
				{
					$validRoleName = $testRoleName;
				}
			}
		}
		catch (Exception $e)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info($e->getMessage());
			}
			$validRoleName = null;
		}
		return $validRoleName;
	}

	/**
	 * Get the list of "permissions" defined on the node $nodeId on the first upstream node where permissions are defined.
	 *
	 * For example: getPermissionsInfoByNode($nodeId) returns
	 * 			array(	'users' => array(userId => array('role1', 'role2'),
	 * 					'groups' => array(groupId => array('role3', 'role4')))
	 * @param integer $nodeId
	 * @return array< String, array< String, String > >
	 */
	public function getPermissionsInfoByNode($nodeId)
	{
		$userResult = array();
		$groupResult = array();
		$result = array('users' => array(), 'groups' => array());
		$defPoint = $this->getDefinitionPoint($nodeId);
		if (!is_null($defPoint))
		{
			$userQuery = $this->getPersistentProvider()->createQuery('modules_generic/userAcl');
			$userQuery->add(Restrictions::eq('documentId', $defPoint));
			$groupQuery = $this->getPersistentProvider()->createQuery('modules_generic/groupAcl');
			$groupQuery->add(Restrictions::eq('documentId', $defPoint));
			$groupResult = $this->getPersistentProvider()->find($groupQuery);
			$userResult = $this->getPersistentProvider()->find($userQuery);
		}

		foreach ($userResult as $acl)
		{
			$userId = $acl->getUser()->getId();
			$role = $acl->getRole();
			if (array_key_exists($userId, $result['users']))
			{
				array_push($result['users'][$userId], $role);
			}
			else
			{
				$result['users'][$userId] = array($role);
			}
		}

		foreach ($groupResult as $acl)
		{
			$groupId = $acl->getGroup()->getId();
			$role = $acl->getRole();
			if (array_key_exists($groupId, $result['groups']))
			{
				array_push($result['groups'][$groupId], $role);
			}
			else
			{
				$result['groups'][$groupId] = array($role);
			}
		}
		return $result;
	}

	/**
	 * Get the list of accessors for a given role on a given node.
	 * @param string $roleName
	 * @param integer $nodeId
	 * @return array
	 */
	public function getACLForNode($nodeId)
	{
		$defPoint = $this->getDefinitionPoint($nodeId);
		if (!is_null($defPoint))
		{
			$userQuery = generic_UserAclService::getInstance()->createQuery();
			$userQuery->add(Restrictions::eq('documentId', $defPoint));
			$groupQuery = generic_GroupAclService::getInstance()->createQuery();
			$groupQuery->add(Restrictions::eq('documentId', $defPoint));
			$groupResult = $this->getPersistentProvider()->find($groupQuery);
			$userResult = $this->getPersistentProvider()->find($userQuery);
			return array_merge($userResult, $groupResult);
		}
		return array();
	}

	/**
	 * Assign role $roleName to user $user for the domain $domain (array of node ids).
	 *
	 * For example: addRoleToUser(users_persistentdocument_user, 'modules_news.developper', array($permissionModuleRootNodeId))
	 * @param users_persistentdocument_user $user
	 * @param string $roleName
	 * @param array<Integer> $domain node identifiers
	 */
	public function addRoleToUser($user, $roleName, $domain)
	{
		try
		{
			$this->getTransactionManager()->beginTransaction();
			foreach ($domain as $nodeId)
			{
				if (!$this->userHasRole($user, $roleName, $nodeId))
				{
					$acl = generic_UserAclService::getInstance()->getNewDocumentInstance();
					$acl->setUser($user);
					$acl->setRole($roleName);
					$acl->setDocumentId($nodeId);
					$acl->save();
				}
				else if (Framework::isDebugEnabled()) 
				{
					Framework::debug(__METHOD__ . ' user '.$user->getId().' has already the role '.$roleName.' on '.$nodeId);
				}
			}
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
		}
	}
		
	/**
	 * @param users_persistentdocument_user $user
	 * @param string $roleName
	 * @param integer $nodeId
	 * @return boolean
	 */
	private function userHasRole($user, $roleName, $nodeId)
	{
		$userAcl = generic_UserAclService::getInstance()->createQuery()
			->add(Restrictions::eq('user', $user->getId()))
			->add(Restrictions::eq('role', $roleName))
			->add(Restrictions::eq('documentId', $nodeId))
			->findUnique();
		return $userAcl !== null;
	}

	/**
	 * Remove permissions for a valid user $user granted by role $roleName on domains $domain.
	 * If $domain is null or empty, all userAcls matching ($user, $roleName) are deleted.
	 * If $roleName is null, $domain is ignored and all userAcls mathcing $user are deleted.
	 *
	 * For example: removeUserPermission(users_persistentdocument_user, 'aRole', array($nodeId1, $nodeId2))
	 * @param users_persistentdocument_user $user
	 * @param string $roleName
	 * @param array<Integer> $domain
	 */
	public function removeUserPermission($user, $roleName = null, $domain = null)
	{
		$affectedNodes = array();
		$query = $this->getPersistentProvider()->createQuery('modules_generic/userAcl');
		$query->add(Restrictions::eq('user.id', $user->getId()));
		if (!is_null($roleName))
		{
			$query->add(Restrictions::eq('role', $roleName));
			if (count($domain) > 0)
			{
				$query->add(Restrictions::in('documentId', $domain));
			}
		}
		$acls = $this->getPersistentProvider()->find($query);
		try
		{
			$this->getTransactionManager()->beginTransaction();
			foreach ($acls as $acl)
			{
				$affectedNodes[$acl->getDocumentId()] = 0;
				$acl->delete();
			}
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
		}
		$this->postRemove($affectedNodes);
	}

	/**
	 * Assign role $roleName to group $group for the domains $domain (array of node ids)..
	 *
	 * For example: addRoleToGroup(users_persistentdocument_group, 'modules_news.developper', array($permissionModuleRootNode))
	 * @param users_persistentdocument_group $group
	 * @param string $rolename
	 * @param array<Integer> $domain
	 */
	public function addRoleToGroup($group, $roleName, $domain)
	{
		try
		{
			$this->getTransactionManager()->beginTransaction();
			foreach ($domain as $nodeId)
			{
				if (!$this->groupHasRole($group, $roleName, $nodeId))
				{
					$acl = generic_GroupAclService::getInstance()->getNewDocumentInstance();
					$acl->setGroup($group);
					$acl->setRole($roleName);
					$acl->setDocumentId($nodeId);
					$acl->save();
				}
				else if (Framework::isDebugEnabled()) 
				{
					Framework::debug(__METHOD__ . ' group '.$group->getId().' has already the role '.$roleName.' on '.$nodeId);
				}
			}
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
		}
	}
	
	/**
	 * @param users_persistentdocument_group $group
	 * @param string $roleName
	 * @param integer $nodeId
	 * @return boolean
	 */
	private function groupHasRole($group, $roleName, $nodeId)
	{
		$groupAcl = generic_GroupAclService::getInstance()->createQuery()
			->add(Restrictions::eq('group', $group->getId()))
			->add(Restrictions::eq('role', $roleName))
			->add(Restrictions::eq('documentId', $nodeId))
			->findUnique();
		return $groupAcl !== null;
	}

	/**
	 * Remove permissions for group $group granted by role $roleName on domain $domain.
	 * If $domain is null (or empty), all groupAcls matching ($group, $roleName) are deleted.
	 * If $roleName is null, $domain is ignored and all groupAcls matching $group are deleted.
	 *
	 * @param users_persistentdocument_group $group
	 * @param string $roleName if null, remove all permissions entries attached to the group $group
	 * @param array<Integer> $domain node identifiers.
	 */
	public function removeGroupPermission($group, $roleName = null, $domain = null)
	{
		$affectedNodes = array();
		$query = $this->getPersistentProvider()->createQuery('modules_generic/groupAcl');
		$query->add(Restrictions::eq('group.id', $group->getId()));
		if (!is_null($roleName))
		{
			$query->add(Restrictions::eq('role', $roleName));
			// We only look for domains if a role has been specified.
			if (count($domain) > 0)
			{
				$query->add(Restrictions::in('documentId', $domain));
			}
		}
		$acls = $this->getPersistentProvider()->find($query);
		try
		{
			$this->getTransactionManager()->beginTransaction();
			foreach ($acls as $acl)
			{
				$affectedNodes[$acl->getDocumentId()] = 0;
				$acl->delete();
			}
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
		}
		$this->postRemove($affectedNodes);
	}
	
	/**
	 * @param string $permission
	 * @param integer $nodeId
	 * @param boolean $onMissingPermission returned value if permission does not exist
	 */
	public function currentUserHasPermission($permission, $nodeId, $onMissingPermission = true)
	{
		$user = users_UserService::getInstance()->getAutenticatedUser();
		return $this->hasPermission($user, $permission, $nodeId, $onMissingPermission);
	}
	
	/**
	 * Checks if the user $user has the permission $permission on node $nodeId.
	 *
	 * For example: hasPermission(users_persistentdocument_user, 'modules_news.edit', $nodeId)
	 * @param users_persistentdocument_user $user
	 * @param string $permission
	 * @param integer $nodeId element of a possible domain
	 * @param boolean $onMissingPermission returned value if permission does not exist
	 * @return boolean
	 */
	public function hasPermission($user, $permission, $nodeId, $onMissingPermission = true)
	{
		$roleservice = $this->getRoleServiceByRole($permission);
		if ($roleservice === null)
		{
			Framework::error('Missing rights.xml configuration file for permission ' . $permission);
			return $onMissingPermission;
		}
		elseif( !$roleservice->hasPermission($permission))
		{
			Framework::error('Missing permission ' . $permission . ' in rights.xml configuration file');
			return $onMissingPermission;
		}	
		if ($user === null) {$user = users_AnonymoususerService::getInstance()->getAnonymousUser();}
		
		if ($user->getIsroot())
		{
			return true;
		}
		elseif ($permission == self::ALL_PERMISSIONS)
		{
			return false;
		}
		
		$accessors = $this->getAccessorIdsByUser($user);
		$parts = explode('.', $permission);
		$def = null;
		if (count($parts) > 1)
		{
			$def = $this->getDefinitionPointForPackage($nodeId, $parts[0]);
		}
		if ($def === null)
		{
			return false;
		}
		else
		{
			return $this->getPersistentProvider()->checkCompiledPermission($accessors, $permission, $def);
		}
	}	

	/**
	 * @param users_persistentdocument_user $user
	 * @param string $permission
	 * @param integer $nodeId element of a possible domain
	 * @param boolean $onMissingPermission returned value if permission does not exist
	 */
	public function checkPermission($user, $permission, $nodeId, $onMissingPermission = true)
	{
		if (!$this->hasPermission($user, $permission, $nodeId, $onMissingPermission))
		{
			$message = str_replace(array('_', '.'), '-', $permission);
			$data = explode('-', $message);
			if (count($data) > 1 && $data[0] == 'modules')
			{
				$moduleName = $data[1];
			}
			else
			{
				$moduleName = 'generic';
			}
			$key = 'modules.'. $moduleName . '.errors.' . ucfirst($message);
			throw new BaseException($message , $key);
		}
	}

	
	/**
	 * Clear all permissions defined on node $nodeId.
	 *
	 * @param integer $nodeId
	 * @param string $packageName (ex: modules_website)
	 * @return Array<String>
	 */
	public function clearNodePermissions($nodeId, $packageName = null)
	{
		// Get all the ACL documents defined for the node...
		$query = $this->getPersistentProvider()->createQuery('modules_generic/userAcl');
		$query->add(Restrictions::eq('documentId', $nodeId));
		if (!is_null($packageName))
		{
			$query->add(Restrictions::like('role', $packageName, MatchMode::START()));
		}
		$userAcls = $this->getPersistentProvider()->find($query);
		$query = $this->getPersistentProvider()->createQuery('modules_generic/groupAcl');
		$query->add(Restrictions::eq('documentId', $nodeId));
		if (!is_null($packageName))
		{
			$query->add(Restrictions::like('role', $packageName, MatchMode::START()));
		}

		$groupAcls = $this->getPersistentProvider()->find($query);
		$deletedRoles = array();
		try
		{
			//...and delete them.
			$this->getTransactionManager()->beginTransaction();
			foreach ($userAcls as $acl)
			{
				$acl->delete();
				$deletedRoles[] = $acl->getRole();
			}

			foreach ($groupAcls as $acl)
			{
				$acl->delete();
				$deletedRoles[] = $acl->getRole();
			}
			// We clean compiled entries as well
			$this->getPersistentProvider()->removeACLForNode($nodeId, $packageName);
			$this->getTransactionManager()->commit();
			return array_unique($deletedRoles);
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
		}
		return array();
	}


	/**
	 * Apply first "upstream" permissions on nodeId. Exisiting permissions are wiped out.
	 *
	 * @param integer $nodeId
	 */
	public function setPermissionsFromParent($nodeId)
	{
		$userResult = array();
		$groupResult = array();
		// If permissions are defined on the node, they are first wiped out
		if ($this->isDefinitionPoint($nodeId))
		{
			$this->clearNodePermissions($nodeId);
		}
		// Get the parent node defining permissions
		$defPoint = $this->getDefinitionPoint($nodeId);
		if (!is_null($defPoint))
		{
			$userQuery = $this->getPersistentProvider()->createQuery('modules_generic/userAcl');
			$userQuery->add(Restrictions::eq('documentId', $defPoint));
			$groupQuery = $this->getPersistentProvider()->createQuery('modules_generic/groupAcl');
			$groupQuery->add(Restrictions::eq('documentId', $defPoint));
			$groupResult = $this->getPersistentProvider()->find($groupQuery);
			$userResult = $this->getPersistentProvider()->find($userQuery);
		}
		try{
			// Clone corresponding acls.
			$this->getTransactionManager()->beginTransaction();
			foreach ($userResult as $acl)
			{
				$derivedAcl = generic_UserAclService::getInstance()->getNewDocumentInstance();
				$derivedAcl->setUser($acl->getUser());
				$derivedAcl->setRole($acl->getRole());
				$derivedAcl->setDocumentId($nodeId);
				$derivedAcl->save();
			}

			foreach ($groupResult as $acl)
			{
				$derivedAcl = generic_GroupAclService::getInstance()->getNewDocumentInstance();
				$derivedAcl->setGroup($acl->getGroup());
				$derivedAcl->setRole($acl->getRole());
				$derivedAcl->setDocumentId($nodeId);
				$derivedAcl->save();
			}
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
		}
	}

	/**
	 * Fetches all the user's roles (directly attributed to him or via a group he belongs to). If
	 * $module is not null, the results are filtered by module name.
	 *
	 * For example: getRolesByUser(users_persistentdocument_user, 'news') would return an array that looks like
	 * 			array( 	'modules_news.writer' => array( 10, 11, 17)
	 * 					'modules_news.validator' => array(11, 17)  ).
	 * @param users_persistentdocument_user $user
	 * @param string $module
	 * @return array<String, array<Integer>> where the array key is a qualified roleName.
	 */
	public function getRolesByUser($user, $module = null)
	{
		$result = array();
		$userQuery = $this->getPersistentProvider()->createQuery('modules_generic/userAcl');
		$userQuery->add(Restrictions::eq('user.id', $user->getId()));
		
		$userResults = $this->getPersistentProvider()->find($userQuery);
		$groups = $user->getGroupsArray();

		if (count($groups)>0)
		{
			$groupQuery = $this->getPersistentProvider()->createQuery('modules_generic/groupAcl');
			$groupQuery->add(Restrictions::in('group', DocumentHelper::getIdArrayFromDocumentArray($groups)));
			$groupResults = $this->getPersistentProvider()->find($groupQuery);
		}
		foreach ($userResults as $acl)
		{
			$role = $acl->getRole();
			$node = $acl->getDocumentId();
			if (!is_null($module))
			{
				$elems = explode(".", $role);
				if ($elems[0] != "modules_" . $module)
				{
					continue;
				}
			}
			if( !array_key_exists($role, $result))
			{
				$result[$role] = array(intval($node));
			}
			else
			{
				array_push($result[$role], intval($node));
			}
		}
		foreach ($groupResults as $acl)
		{
			$role = $acl->getRole();
			$node = $acl->getDocumentId();
			if (!is_null($module))
			{
				$elems = explode(".", $role);
				if ($elems[0] != "modules_" . $module)
				{
					continue;
				}
			}
			if (!array_key_exists($role, $result))
			{
				$result[$role] = array(intval($node));
			}
			else
			{
				array_push($result[$role], intval($node));
			}
		}
		return $result;
	}

	/**
	 * Checks if permissions are explicitely defined on $nodeId.
	 *
	 * @param integer $nodeId
	 * @return boolean
	 */
	public function isDefinitionPoint($nodeId)
	{
		return $this->getPersistentProvider()->hasCompiledPermissions($nodeId);
	}

	/**
	 * Finds the first "upstream" permission definition point or null if no permissions are defined on the tree.
	 * 
	 * @param integer $nodeId
	 * @return integer
	 */
	public function getDefinitionPoint($nodeId)
	{
		if ( $this->isDefinitionPoint($nodeId) )
		{
			return $nodeId;
		}
		$currentNode = TreeService::getInstance()->getInstanceByDocumentId($nodeId);
		
		if (is_null($currentNode))
		{
			// FIXME: Here we can't handle virtual tree nodes.
			$rootNodeId = $this->getRootNodeIdByDocumentId($nodeId);
			if ( $this->isDefinitionPoint($rootNodeId) )
			{
				return $rootNodeId;
			}
			return null;
		}

		$ancestors = $currentNode->getAncestors();
		foreach (array_reverse($ancestors) as $currentNode)
		{
			$currentId = $currentNode->getId();
			if ( $this->isDefinitionPoint($currentId) )
			{
				return $currentId;
			}
		}
		return null;
	}

	/**
	 * @param integer $nodeId
	 * @param string $packageName
	 * @return integer
	 */
	public function isDefinitionPointForPackage($nodeId, $packageName)
	{
		return $this->getPersistentProvider()->hasCompiledPermissionsForPackage($nodeId, $packageName);
	}

	/**
	 * @param integer $nodeId
	 * @param string $packageName
	 * @return integer
	 */
	public function getDefinitionPointForPackage($nodeId, $packageName)
	{
		if ($this->isDefinitionPointForPackage($nodeId, $packageName))
		{
			return $nodeId;
		}
		
		$currentNode = TreeService::getInstance()->getInstanceByDocumentId($nodeId);
		if ($currentNode === null)
		{
			list(, $module) = explode('_', $packageName);
			$moduleService = ModuleBaseService::getInstanceByModuleName($module);
			if ($moduleService !== null)
			{
				$currentNode = $moduleService->getParentNodeForPermissions($nodeId);
			}
			
			if ($currentNode !== null && $this->isDefinitionPointForPackage($currentNode->getId(), $packageName))
			{
				return $currentNode->getId();
			}
		}
		
		$rootNodeId = ModuleService::getInstance()->getRootFolderId(substr($packageName, 8));
		if ($currentNode === null)
		{
			if ($this->isDefinitionPointForPackage($rootNodeId, $packageName))
			{
				return $rootNodeId;
			}
			return null;
		}

		// TODO: something that is not in o(numberOfAncestors)
		$ancestors = array_reverse($currentNode->getAncestors());
		
		if ($rootNodeId != $currentNode->getTreeId())
		{
			$ancestors[] = TreeService::getInstance()->getInstanceByDocumentId($rootNodeId);
		}
		foreach ($ancestors as $currentNode)
		{
			if ($currentNode === null)
			{
				continue;
			}
			$currentId = $currentNode->getId();
			if ( $this->isDefinitionPointForPackage($currentId, $packageName))
			{
				return $currentId;
			}
		}
		
		return null;
	}

	/**
	 * Get the root node Id for the document id $docId.
	 *
	 * @param integer $docId
	 * @return integer
	 */
	private function getRootNodeIdByDocumentId($docId)
	{
		$doc = DocumentHelper::getDocumentInstance($docId);
		$rootFolderId = $doc->getTreeId();
		if ($rootFolderId !== null)
		{
			return $rootFolderId;
		}
		$module = $doc->getPersistentModel()->getModuleName();
		return ModuleService::getInstance()->getRootFolderId(str_replace('modules_', '', $module));
	}

	/**
	 * Recompiles all ACLs for $nodeId
	 *
	 * @param integer $nodeId
	 */
	private function compileACLForNode($nodeId)
	{
		$userResult = array();
		$groupResult = array();
		$defPoint = $this->getDefinitionPoint($nodeId);
		try
		{
			$this->getTransactionManager()->beginTransaction();
			if (!is_null($defPoint))
			{
				$this->getPersistentProvider()->removeACLForNode($defPoint);
				$userQuery = $this->getPersistentProvider()->createQuery('modules_generic/userAcl');
				$userQuery->add(Restrictions::eq('documentId', $defPoint));
				$groupQuery = $this->getPersistentProvider()->createQuery('modules_generic/groupAcl');
				$groupQuery->add(Restrictions::eq('documentId', $defPoint));
				$groupResult = $this->getPersistentProvider()->find($groupQuery);
				$userResult = $this->getPersistentProvider()->find($userQuery);
			}
			foreach ($userResult as $acl)
			{
				$this->getPersistentProvider()->compileACL($acl);
			}
			foreach ($groupResult as $acl)
			{
				$this->getPersistentProvider()->compileACL($acl);
			}
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
		}
	}

	/**
	 * Recompilation of ACLs for all nodes affected by a "removePermission" call.
	 *
	 * @param Array<Integer, Integer> $affectedNodes, where keys are the affected nodes' Ids.
	 */
	private function postRemove($affectedNodes)
	{
		try
		{
			$this->getTransactionManager()->beginTransaction();
			foreach (array_keys($affectedNodes) as $id)
			{
				$this->compileACLForNode($id);
			}
			$this->getTransactionManager()->commit();
		}
		catch (Exception $e)
		{
			$this->getTransactionManager()->rollBack($e);
		}
	}

	/**
	 * Returns the array of users having role $roleName on the document $documentId
	 *
	 * @param string $roleName
	 * @param integer $documentId
	 * @return Array<Integer>
	 */
	public function getUsersByRoleAndDocumentId($roleName,  $documentId = null)
	{
		$result = array();

		if (self::roleExists($roleName))
		{
			Framework::info(__METHOD__ ."($roleName)");
			$userQuery = $this->getPersistentProvider()->createQuery('modules_generic/userAcl');
			$userQuery->add(Restrictions::eq('role', $roleName));
			$groupQuery = $this->getPersistentProvider()->createQuery('modules_generic/groupAcl');
			$groupQuery->add(Restrictions::eq('role', $roleName));
			if ($documentId !== null)
			{
				$packageName = substr($roleName, 0, strpos($roleName, '.'));
				$defPoint = $this->getDefinitionPointForPackage($documentId, $packageName);
				Framework::info(__METHOD__ ."($packageName, $defPoint)");
				if ($defPoint !== null)
				{
					$userQuery->add(Restrictions::eq('documentId', $defPoint));
					$groupQuery->add(Restrictions::eq('documentId', $defPoint));
				}
				else
				{
					return $result;
				}
			}

			$userAcls = $this->getPersistentProvider()->find($userQuery);
			$groupAcls = $this->getPersistentProvider()->find($groupQuery);

			foreach ($userAcls as $acl)
			{
				$result[] =  $acl->getAccessorId();
			}

			foreach	($groupAcls as $acl)
			{
				$group = $this->getDocumentInstance($acl->getAccessorId());
				$userIds = $this->getPersistentProvider()->createQuery('modules_users/user')
					->add(Restrictions::eq('groups.id', $group->getId()))
					->setProjection(Projections::property('id', 'id'))->find();
				foreach ($userIds as $userEntry)
				{
					$result[] = $userEntry['id'];
				}
			}
		}
		return array_unique($result);
	}

	/**
	 * Returns the array of raw accessor id's having the role $roleName on document ID $documentId.
	 *
	 * @param string $roleName
	 * @param integer $documentId
	 * @return Array<Integer>
	 */
	public function getAccessorIdsForRoleByDocumentId($roleName, $documentId)
	{
		$result = array();
		if (self::roleExists($roleName))
		{
			$packageName = 'modules_' . self::getModuleNameByRole($roleName);
			$defPoint = $this->getDefinitionPointForPackage($documentId, $packageName);
			if (is_null($defPoint))
			{
				return $result;
			}
			$userQuery = $this->getPersistentProvider()->createQuery('modules_generic/userAcl');
			$userQuery->add(Restrictions::eq('role', $roleName));
			$groupQuery = $this->getPersistentProvider()->createQuery('modules_generic/groupAcl');
			$groupQuery->add(Restrictions::eq('role', $roleName));

			$userQuery->add(Restrictions::eq('documentId', $defPoint));
			$groupQuery->add(Restrictions::eq('documentId', $defPoint));

			$userAcls = $this->getPersistentProvider()->find($userQuery);
			$groupAcls = $this->getPersistentProvider()->find($groupQuery);

			foreach ($userAcls as $acl)
			{
				$result[] =  $acl->getAccessorId();
			}

			foreach	($groupAcls as $acl)
			{
				$result[] =  $acl->getAccessorId();
			}
		}
		return $result;
	}
	
	/**
	 * Returns the array of raw accessor id's having the permission $permissionName on document ID $documentId.
	 *
	 * @param string $permissionName
	 * @param string $documentId
	 * @return array<int>
	 */
	public function getAccessorIdsForPermissionAndDocumentId($permissionName, $documentId)
	{
		return $this->getPersistentProvider()->getAccessorsByPermissionForNode($permissionName, $documentId);
	}
	

	/**
	 * Predicate on the existence of role $roleName.
	 *
	 * @param string $roleName
	 * @return boolean
	 */
	public static function roleExists($roleName)
	{
		try
		{
			$service = self::getRoleServiceByRole($roleName);
			return array_search($roleName, $service->getRoles()) !== false;
		}
		catch (Exception $e)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info($e->getMessage());
			}
		}
		return false;
	}

	/**
	 * Get the complete list of permissions for user $user on definition point node $node.
	 *
	 * @param users_persistentdocument_user $user
	 * @param f_persistentdocument_PersistentTreeNode $node
	 * @return array<String>
	 */
	public function getPermissionsForUserByDefPointNodeId($user, $nodeId)
	{
		if ($user->getIsroot())
		{
			return array(self::ALL_PERMISSIONS);
		}
		else if (is_null($nodeId))
		{
			return array();
		}
		$accessors = $user->getGroupsArray();
		$accessors[] = $user;

		return $this->getPersistentProvider()->getPermissionsForUserByNode(DocumentHelper::getIdArrayFromDocumentArray($accessors), $nodeId);
	}

	/**
	 * Get the array of accessor Id's for the user $user.
	 *
	 * @param users_persistentdocument_user $user
	 * @return Array<Integer>
	 */
	public function getAccessorIdsByUser($user)
	{
		if (!($user instanceof users_persistentdocument_user))
		{
			throw new IllegalArgumentException('$user must be a users_persistentdocument_user document instance');
		}

		$accessors = $user->getGroupsArray();
		$accessors[] = $user;
		return DocumentHelper::getIdArrayFromDocumentArray($accessors);
	}

	/**
	 * Sends a permission updated event
	 *
	 * @param Array $eventParam
	 */
	public function dispatchPermissionsUpdatedEvent($eventParam)
	{
		indexer_IndexService::getInstance()->scheduleReindexingByUpdatedRoles($eventParam['updatedRoles']);
		f_event_EventManager::dispatchEvent('permissionsUpdated', $this, $eventParam);
	}

	/**
	 * Compiles all defined acls
	 */
	public function compileAllPermissions()
	{
		$tm = $this->getTransactionManager();
		$pp = $this->getPersistentProvider();
		try
		{
			$tm->beginTransaction();
			$acls = $pp->createQuery('modules_generic/userAcl')->add(Restrictions::published())->find();
			foreach ($acls as $acl)
			{
				$pp->compileACL($acl);
			}
			$acls = $pp->createQuery('modules_generic/groupAcl')->add(Restrictions::published())->find();
			foreach ($acls as $acl)
			{
				$pp->compileACL($acl);
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
		}
	}
	
	/**
	 * @param string $forModuleName
	 * @param string $fromModuleName
	 * @param string $configFileName
	 * @throws Exception
	 */
	public function addImportInRight($forModuleName, $fromModuleName, $configFileName)
	{
		$destPath = f_util_FileUtils::buildOverridePath('modules', $forModuleName, 'config', 'rights.xml');
		$result = array('action' => 'ignore', 'path' => $destPath);
		
		$path = change_FileResolver::getNewInstance()->getPath('modules', $fromModuleName, 'config', $configFileName .'.xml');		
		if ($path === null)
		{
			throw new Exception(__METHOD__ . ' file ' . $fromModuleName . '/config/' . $configFileName . '.xml not found');
		}
		
		if (!file_exists($destPath))
		{
			$document = f_util_DOMUtils::fromString('<rights />');
			$result['action'] = 'create';
		}
		else
		{
			$document = f_util_DOMUtils::fromPath($destPath);
		}
		
		$xquery = 'import[@modulename="'.$fromModuleName.'" and @configfilename="'.$configFileName.'"]';
		$importNode = $document->findUnique($xquery, $document->documentElement);
		if ($importNode === null)
		{
			f_util_FileUtils::mkdir(f_util_FileUtils::buildOverridePath('modules', $forModuleName, 'config'));
			$importNode = $document->documentElement->appendChild($document->createElement('import'));	
			$importNode->setAttribute('modulename', $fromModuleName);
			$importNode->setAttribute('configfilename', $configFileName);
			f_util_DOMUtils::save($document, $destPath);
			if ($result['action'] == 'ignore') {$result['action'] = 'update';}
		}
		return $result;
	}
	
	/**
	 * @param string $name
	 * @param array $arguments
	 */
	public function __call($name, $arguments)
	{
		switch ($name)
		{
			case 'hasFrontEndPermission': 
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . ' method');
				return $this->hasPermission($arguments[0], $arguments[1], $arguments[2]);
				
			case 'currentUserHasFrontEndPermission': 
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . ' method');	
				return $this->currentUserHasPermission($arguments[0], $arguments[1]);	
				
			case 'hasExplicitPermission': 
				Framework::error('Call to deleted ' . get_class($this) . '->' . $name . ' method');	
				return $this->hasPermission($arguments[0], $arguments[1], $arguments[2], false);
					
			default: 
				throw new BadMethodCallException('No method ' . get_class($this) . '->' . $name);
		}
	}
}

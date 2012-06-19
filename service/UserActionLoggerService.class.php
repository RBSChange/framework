<?php
/**
 * @method UserActionLoggerService getInstance()
 */
class UserActionLoggerService extends change_BaseService
{
	/**
	 * @param String $actionName
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array $info
	 * @param String $moduleName
	 * @return Integer the log entry id
	 */
	public function addCurrentUserDocumentEntry($actionName, $document, $info, $moduleName)
	{
		return null;
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @param String $actionName
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array $info
	 * @param String $moduleName
	 * @return Integer the log entry id
	 */
	public function addUserDocumentEntry($user, $actionName, $document, $info, $moduleName)
	{
		return null;
	}
}
<?php
/**
 * @method UserActionLoggerService getInstance()
 */
class UserActionLoggerService extends change_BaseService
{
	/**
	 * @param string $actionName
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array $info
	 * @param string $moduleName
	 * @return integer the log entry id
	 */
	public function addCurrentUserDocumentEntry($actionName, $document, $info, $moduleName)
	{
		return null;
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @param string $actionName
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param array $info
	 * @param string $moduleName
	 * @return integer the log entry id
	 */
	public function addUserDocumentEntry($user, $actionName, $document, $info, $moduleName)
	{
		return null;
	}
}
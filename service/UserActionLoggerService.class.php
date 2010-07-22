<?php
class UserActionLoggerService extends BaseService
{
	/**
	 * @var UserActionLoggerService
	 */
	private static $instance;

	/**
	 * @return UserActionLoggerService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			if (f_util_ClassUtils::classExists('useractionlogger_ModuleService'))
			{
				self::$instance = self::getServiceClassInstance('useractionlogger_ModuleService');
			}
			else
			{
				if (Framework::isInfoEnabled())
				{
					Framework::info('Module useractionlogger not installed, logging descativated');
				}
				self::$instance = self::getServiceClassInstance(get_class());
			}
		}
		return self::$instance;
	}
	
	
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
}
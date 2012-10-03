<?php
/**
 * @deprecated
 */
class f_persistentdocument_TransactionManager
{

	/**
	 * @deprecated
	 */
	public static function getInstance()
	{
		return \Change\Db\Provider::getInstance();
	}

	/**
	 * @deprecated
	 */
	public function getPersistentProvider()
	{
		return \Change\Db\Provider::getInstance();
	}

	/**
	 * @deprecated
	 */
	public function isDirty()
	{
		return self::getInstance()->isTransactionDirty();
	}

	/**
	 * @deprecated
	 */
	public static function reset($persistentProvider = null)
	{
		Framework::deprecated('Removed method');
	}
}
<?php
/**
 * @deprecated
 */
class f_persistentdocument_TransactionManager
{
	/**
	 * @var f_persistentdocument_TransactionManager
	 */
	private static $instance;
	
	/**
	 * @var \Change\Db\DbProvider
	 */
	private $wrapped;
	
	/**
	 * @deprecated
	 */
	protected function __construct()
	{
		$this->wrapped = f_persistentdocument_PersistentProvider::getInstance();
	}
	
	/**
	 * @deprecated
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * @deprecated wrapped method
	 */
	public function __call($name, $args)
	{
		return call_user_func_array(array($this->wrapped, $name), $args);
	}

	/**
	 * @deprecated
	 */
	public function getPersistentProvider()
	{
		return $this->wrapped;
	}

	/**
	 * @deprecated
	 */
	public function isDirty()
	{
		return $this->wrapped->isTransactionDirty();
	}

	/**
	 * @deprecated
	 */
	public static function reset($persistentProvider = null)
	{
		Framework::deprecated('Removed method');
	}
}
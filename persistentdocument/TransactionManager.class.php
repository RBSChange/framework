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
	 * @var f_persistentdocument_PersistentProvider
	 */
	private $wrapped;
	
	/**
	 * @var integer
	 */
	protected $transactionCount = 0;
	
	/**
	 * @var boolean
	 */
	protected $transactionDirty = false;
	
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
	 * @throws \Exception
	 */
	protected final function checkDirty()
	{
		if ($this->transactionDirty)
		{
			throw new \Exception('Transaction is dirty');
		}
	}
	
	/**
	 * @return void
	 */
	public function beginTransaction()
	{
		$this->checkDirty();
		if ($this->transactionCount == 0)
		{
			$this->transactionCount++;
			$this->wrapped->beginTransaction();
			indexer_IndexService::getInstance()->beginIndexTransaction();
		}
		else
		{
			$embededTransaction = intval(Framework::getConfigurationValue('databases/default/embededTransaction', '15'));
			$this->transactionCount++;
			if ($this->transactionCount > $embededTransaction)
			{
				Framework::warn('embeded transaction: ' . $this->transactionCount);
			}
		}
	}
	
	/**
	 * @param boolean $isolatedWrite make sense in the context of read-write separated database. 
	 * 	Set to true if the next client request does not care about the data you wrote. It will then perform reads on read database.
	 * @throws Exception if bad transaction count
	 * @return void
	 */
	public function commit($isolatedWrite = false)
	{
		$this->checkDirty();
		if ($this->transactionCount <= 0)
		{
			throw new \Exception('commit-bad-transaction-count ('.$this->transactionCount.')');
		}
		
		if ($this->transactionCount == 1)
		{
			
			$this->wrapped->commit();
			
			$this->wrapped->beginTransaction();
			indexer_IndexService::getInstance()->commitIndex();
			$this->wrapped->commit();
		}
		$this->transactionCount--;
	}
	
	/**
	 * Cancel transaction.
	 * @param Exception $e
	 * @throws BaseException('rollback-bad-transaction-count') if rollback called while no transaction
	 * @throws Change\Db\Exception\TransactionCancelledException on embeded transaction
	 * @return Exception the given exception so it is easy to throw it
	 */
	public function rollBack($e = null)
	{
		Framework::warn(__METHOD__ . ' called');
		if ($this->transactionCount == 0)
		{
			Framework::warn(__METHOD__ . ' => bad transaction count (no transaction)');
			throw new Exception('rollback-bad-transaction-count');
		}
		$this->transactionCount--;
		
		if (!$this->transactionDirty)
		{
			$this->transactionDirty = true;
			$this->wrapped->clearDocumentCache();
			indexer_IndexService::getInstance()->rollBackIndex();
			$this->wrapped->rollBack();
		}
	
		if ($this->transactionCount == 0)
		{
			$this->transactionDirty = false;
		}
		else
		{
			if (!($e instanceof \Change\Transaction\RollbackException))
			{
				$e = new \Change\Transaction\RollbackException($e);
			}
			throw $e;
		}
		return ($e instanceof \Change\Transaction\RollbackException) ? $e->getPrevious() : $e;
	}
	
	/**
	 * @return boolean
	 */
	public function hasTransaction()
	{
		return $this->transactionCount > 0;
	}
	
	/**
	 * @deprecated
	 */
	public function isDirty()
	{
		return $this->transactionDirty;
	}

	/**
	 * @deprecated
	 */
	public static function reset($persistentProvider = null)
	{
		Framework::deprecated('Removed method');
	}
}
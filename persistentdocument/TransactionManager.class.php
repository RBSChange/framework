<?php
/**
 * @package framework.persistentdocument
 */
class f_persistentdocument_TransactionManager
{
	private static $instance;

	/**
	 * @var f_persistentdocument_PersistentProvider
	 */
	protected $persistentProvider;
	protected $transactionCount = 0;
	private $dirty = false;

	/**
	 * Empty
	 */
	protected function __construct()
	{
	}

	public function __destruct()
	{
		if ($this->hasTransaction())
		{
			Framework::warn(__METHOD__ . ' called while active transaction (' . $this->transactionCount . ')');
		}
	}

	/**
	 * @param f_persistentdocument_PersistentProvider $persistentProvider
	 * @return f_persistentdocument_TransactionManager Singleton
	 */
	public static function getInstance($persistentProvider = null)
	{
		if (self::$instance === null)
		{
			$instance = new self();
			if ($persistentProvider === null)
			{
				$instance->persistentProvider = f_persistentdocument_PersistentProvider::getInstance();
			}
			else
			{
				$instance->persistentProvider = $persistentProvider;
			}
			self::$instance = $instance;
		}
		return self::$instance;
	}

	/**
	 * @return f_persistentdocument_PersistentProvider
	 */
	public function getPersistentProvider()
	{
		return $this->persistentProvider;
	}

	protected final function checkDirty()
	{
		if ($this->dirty)
		{
			throw new Exception('Transaction is dirty');
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
			$this->persistentProvider->beginTransaction();
		}
		else
		{
			$this->transactionCount++;
			if ($this->transactionCount > 5)
			{
				Framework::warn('embeded transaction: ' . $this->transactionCount);
			}
		}
	}

	/**
	 * @param boolean $isolatedWrite make sense in the context of read-write separated database. Set to true if the next client request does not care about the data you wrote. It will then perform reads on read database. 
	 * @throws Exception if bad transaction count
	 * @return void
	 */
	public function commit($isolatedWrite = false)
	{
		$this->checkDirty();
		if ($this->transactionCount <= 0)
		{
			if (Framework::isWarnEnabled())
			{
				Framework::debug('TransactionManager::commit() => bad transaction count ('.$this->transactionCount.')');
			}
			throw new BaseException('commit-bad-transaction-count ('.$this->transactionCount.')');
		}
		if ($this->transactionCount == 1)
		{
			$this->persistentProvider->commit();
		}
		$this->transactionCount--;
	}

	/**
	 * cancel transaction.
	 * @param Exception $e
	 * @throws BaseException('rollback-bad-transaction-count') if rollback called while no transaction
	 * @throws TransactionCancelledException on embeded transaction
	 * @return Exception the given exception so it is easy to throw it
	 */
	public function rollBack($e = null)
	{
		if (Framework::isWarnEnabled())
		{
			Framework::warn('TransactionManager->rollBack called');
			if (!($e instanceof TransactionCancelledException) && $e !== null)
			{
				Framework::exception($e);
			}
		}
		if ($this->transactionCount == 0)
		{
			if (Framework::isWarnEnabled())
			{
				Framework::warn('TransactionManager->rollBack() => bad transaction count (no transaction)');
			}
			throw new BaseException('rollback-bad-transaction-count');
		}
		$this->transactionCount--;
		if (!$this->dirty)
		{
			$this->dirty = true;
			$this->persistentProvider->rollBack();
		}
		if ($this->transactionCount == 0)
		{
			$this->dirty = false;
		}
		else
		{
			throw new TransactionCancelledException($e);
		}
		return $e;
	}

	/**
	 * @return boolean
	 */
	public function hasTransaction()
	{
		return $this->transactionCount > 0;
	}

	/**
	 * @return boolean
	 */
	public function isDirty()
	{
		return $this->dirty;
	}

	/**
	 * For test usage only
	 * @param f_persistentdocument_PersistentProvider $persistentProvider
	 * @return void
	 */
	public static function reset($persistentProvider = null)
	{
		$instance = new f_persistentdocument_TransactionManager();
		if (is_null($persistentProvider))
		{
			throw new Exception("Persistent provider can not be null");
		}
		$instance->persistentProvider = $persistentProvider;
		self::$instance = $instance;
	}
}
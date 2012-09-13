<?php
class StatmentMysql
{
	/**
	 * @var PDOStatement
	 */
	private $stmt;

	/**
	 * @var String
	 */
	private $sql;

	/**
	 * @var array
	 */
	private $replacements;

	private $params = array();

	public static $time = array('count' => 0, 'prepare' => 0 , 'countexec' => 0, 'err' => 0, 'exec' => 0, 'read' => 0);


	/**
	 * @param string $sql
	 * @param PDO $driver
	*/
	function __construct($sql, $driver)
	{
		self::$time['count']++;
		$start = microtime(true);
		$this->sql = $sql;
		$this->stmt = $driver->prepare($sql);
		self::$time['prepare'] += (microtime(true) - $start);

		if ($this->stmt === false)
		{
			self::$time['err']++;
			Framework::error($this->sql);
			$errorCode = $driver->errorCode();
			$msg = "Driver ERROR Code (". $errorCode . ") : " . var_export($driver->errorInfo(), true)."\n";
			$e = new f_DatabaseException($errorCode, $msg);
			Framework::exception($e);
			throw $e;
		}

	}

	public function bindValue($parameterName, $value, $type = null)
	{
		$this->stmt->bindValue($parameterName, $value, $this->getStatmentType($type));
		if (Framework::isBenchEnabled())
		{
			$this->params[$parameterName] = $value;
		}
	}
	
	/**
	 * @param string $parameterName
	 * @param PropertyInfo $propertyInfo
	 * @param mixed $value
	 * @return string
	 */
	public function bindPropertyValue($propertyInfo, $value)
	{
		$name = ':p' . $propertyInfo->getDbMapping();
		switch ($propertyInfo->getType())
		{
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_DATETIME:
				if (empty($value))
				{
					$this->bindValue($name, null, PersistentProviderConst::PARAM_NULL);
				}
				else if (is_long($value))
				{
					$this->bindValue($name, date("Y-m-d H:i:s", $value), PersistentProviderConst::PARAM_STR);
				}
				else
				{
					$this->bindValue($name, $value, PersistentProviderConst::PARAM_STR);
				}
				break;
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_BOOLEAN:
				$this->bindValue($name, $value ? 1 : 0, PersistentProviderConst::PARAM_INT);
				break;
			case f_persistentdocument_PersistentDocument::PROPERTYTYPE_INTEGER:
				if ($value === null)
				{
					$this->bindValue($name, null, PersistentProviderConst::PARAM_NULL);
				}
				else
				{
					$this->bindValue($name, $value, PersistentProviderConst::PARAM_INT);
				}
				break;
			default:
				if ($value === null)
				{
					$this->bindValue($name, null, PersistentProviderConst::PARAM_NULL);
				}
				else
				{
					$this->bindValue($name, strval($value), PersistentProviderConst::PARAM_STR);
				}
				break;
		}
		return $name;	
	}

	public function execute($parameters = null)
	{
		if (Framework::isBenchEnabled())
		{
			Framework::startBench();
			if (is_array($parameters))
			{
				foreach ($parameters as $parameterName => $parameterValue)
				{
					$this->params[$parameterName] = $parameterValue;
				}
			}
		}

		self::$time['countexec']++;
		$start = microtime(true);

		if (!$this->stmt->execute($parameters) && $this->stmt->errorCode() != '00000')
		{
			self::$time['err']++;
			if (Framework::isBenchEnabled())
			{
				$trace = "SQL ERROR||". str_replace(array("\n", "\t"), '', $this->sql.'||'. var_export($this->params, true));
				Framework::endBench(str_replace('||', "\t", $trace));
			}
			return false;
		}

		$time = (microtime(true) - $start);
		self::$time['exec'] += $time;

		if (Framework::isBenchEnabled())
		{
			if ($time > 0.01)
			{
				$trace = 'SQL SLOWQUERY||' .$time . '||' . str_replace(array("\n", "\t"), '', $this->sql . "||" . var_export($this->params, true));
			}
			else
			{
				$trace = "SQL||". str_replace(array("\n", "\t"), '', $this->sql.'||'. var_export($this->params, true));
			}

			Framework::endBench(str_replace('||', "\t", $trace));
		}
		return true;
	}

	public function fetch($mode)
	{
		$start = microtime(true);
		$fetch =  $this->stmt->fetch($this->getStatmentFetchMode($mode));
		self::$time['read'] += (microtime(true) - $start);
		return $fetch;
	}

	public function fetchAll($mode)
	{
		$start = microtime(true);
		$fetch =  $this->stmt->fetchAll($this->getStatmentFetchMode($mode));
		self::$time['read'] += (microtime(true) - $start);
		return $fetch;
	}

	public function fetchColumn($columnNumber = 0)
	{
		return $this->stmt->fetchColumn($columnNumber);
	}

	public function closeCursor()
	{
		if ($this->stmt !== false)
		{
			$this->stmt->closeCursor();
		}
	}

	public function errorCode()
	{
		return $this->stmt->errorCode();
	}

	public function errorInfo()
	{
		return $this->stmt->errorInfo();
	}

	public function rowCount()
	{
		return $this->stmt->rowCount();
	}

	/**
	 * @param string|integer $mode
	 * @return integer
	 */
	private function getStatmentType($type)
	{
		if (is_numeric($type)) {return $type;}
		
		switch ($type)
		{
			case PersistentProviderConst::PARAM_INT:
				return PDO::PARAM_INT;
			case PersistentProviderConst::PARAM_NULL:
				return PDO::PARAM_NULL;
			case PersistentProviderConst::PARAM_STR:
			case PersistentProviderConst::PARAM_DATE:
			case PersistentProviderConst::PARAM_LOB:
			case PersistentProviderConst::FETCH_NUM:
				return PDO::PARAM_STR;
			default:
				return PDO::PARAM_STR;
		}
	}

	/**
	 * @param string|integer $mode
	 * @return integer
	 */
	private function getStatmentFetchMode($mode)
	{
		if (is_numeric($mode)) {return $mode;}
		switch ($mode)
		{
			case PersistentProviderConst::FETCH_NUM:
				return PDO::FETCH_NUM;
			case PersistentProviderConst::FETCH_COLUMN:
				return PDO::FETCH_COLUMN;
			default:
				return PDO::FETCH_ASSOC;
		}
	}

	public function __destruct()
	{
		$this->stmt->closeCursor();
	}
}
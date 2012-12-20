<?php

class MysqlStatment
{
	const FETCH_ASSOC = 'FETCH_ASSOC';
	const FETCH_NUM = 'FETCH_NUM';
	const FETCH_COLUMN = 'FETCH_COL';
	
	/**
	 * @var PDOStatement
	 */
	private $stmt;

	/**
	 * @var f_persistentdocument_PersistentProvider
	 */
	private $provider;

	/**
	 * @var string
	 */
	private $errorMessage;

	/**
	 * @var string
	 */
	protected $sql;
	
	/**
	 * @param f_persistentdocument_PersistentProvider $provider
	 * @param string $sql
	 * @param StatmentParameter[] $parameters
	 */
	public function __construct($provider, $sql, $parameters = null)
	{
		$this->provider = $provider;
		$this->sql = $sql;
		if (is_array($parameters))
		{
			foreach ($parameters as $parameter)
			{
				if ($parameter instanceof StatmentParameter)
				{
					$this->addParameter($parameter);
				}
			}
		}
	}
		
	/**
	 * @return string
	 */
	public function getSql()
	{
		return $this->sql;
	}
	
	/**
	 * @throws Exception
	 * @return PDOStatement
	 */
	public function getPDOStatment()
	{
		if ($this->stmt === null)
		{
			$pdo = $this->provider->getDriver();
			$this->stmt = $pdo->prepare($this->sql);
			if ($this->stmt === false)
			{
				$errorCode = $pdo->errorCode();
				$this->errorMessage = "Driver ERROR Code (" . $errorCode . ") : " . var_export($pdo->errorInfo(), true);
				throw new Exception($this->errorMessage);
			}
		}
		elseif ($this->stmt === false)
		{
			$this->errorMessage = "Statment already closed.";
			throw new Exception($this->errorMessage);
		}
		return $this->stmt;
	}

	/**
	 * @param StatmentParameter $parameter
	 * @return MysqlStatment
	 */
	public function addParameter(StatmentParameter $parameter)
	{
		$this->getPDOStatment()->bindValue($parameter->getName(), $parameter->getValue(), $this->getStatmentType($parameter->getType()));
		return $this;
	}

	/**
	 * @return void
	 */
	public function close()
	{
		if ($this->stmt !== false && $this->stmt !== null)
		{
			$this->stmt->closeCursor();
		}
		$this->provider = null;
		$this->stmt = false;
	}

	/**
	 * @param string $parameterName
	 * @param mixed $value
	 * @param string $type
	 */
	public function bindValue($parameterName, $value, $type = null)
	{
		$this->getPDOStatment()->bindValue($parameterName, $value, $this->getStatmentType($type));
	}

	/**
	 * @param string $parameterName
	 * @param PropertyInfo $propertyInfo
	 * @param mixed $value
	 * @return string
	 */
	public function bindPropertyValue(PropertyInfo $propertyInfo, $value)
	{
		$name = ':p' . $propertyInfo->getName();
		switch ($propertyInfo->getType())
		{
			case 'DateTime':
				if (empty($value))
				{
					$this->bindValue($name, null, StatmentParameter::NIL);
				}
				else if (is_long($value))
				{
					$this->bindValue($name, date("Y-m-d H:i:s", $value), StatmentParameter::STR);
				}
				else
				{
					$this->bindValue($name, $value, StatmentParameter::STR);
				}
				break;
			case 'Boolean' :
				$this->bindValue($name, $value ? 1 : 0, StatmentParameter::INT);
				break;
			case 'Integer' :
				if ($value === null)
				{
					$this->bindValue($name, null, StatmentParameter::NIL);
				}
				else
				{
					$this->bindValue($name, $value, StatmentParameter::INT);
				}
				break;
			default :
				if ($value === null)
				{
					$this->bindValue($name, $value, StatmentParameter::NIL);
				}
				else
				{
					$this->bindValue($name, strval($value), StatmentParameter::STR);
				}
				break;
		}
		return $name;
	}

	/**
	 * @param StatmentParameter[] $parameters
	 * @return boolean
	 */
	public function execute($parameters = null)
	{
		if (is_array($parameters))
		{
			foreach ($parameters as $parameter)
			{
				if ($parameter instanceof StatmentParameter)
				{
					$this->addParameter($parameter);
				}
			}
		}
		$stmt = $this->getPDOStatment();
		if (!$stmt->execute() && $this->stmt->errorCode() != '00000')
		{
			$errorCode = $this->stmt->errorCode();
			$this->errorMessage = "Driver ERROR Code (" . $errorCode . ") : " . var_export($this->stmt->errorInfo(), true);
			return false;
		}
		return true;
	}

	/**
	 * @return string|null
	 */
	public function getErrorMessage()
	{
		return $this->errorMessage;
	}

	/**
	 * @param string $mode
	 * @return array|false
	 */
	public function fetch($mode)
	{
		return $this->stmt->fetch($this->getStatmentFetchMode($mode));
	}

	/**
	 * @param string $mode
	 * @return array
	 */
	public function fetchAll($mode)
	{
		return $this->stmt->fetchAll($this->getStatmentFetchMode($mode));
	}

	/**
	 *
	 * @param integer $columnNumber
	 * @return string|false
	 */
	public function fetchColumn($columnNumber = 0)
	{
		return $this->stmt->fetchColumn($columnNumber);
	}

	public function closeCursor()
	{
		$this->close();
	}

	/**
	 * @return integer
	 */
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
		switch ($type)
		{
			case PDO::PARAM_INT:
			case StatmentParameter::INT :
				return PDO::PARAM_INT;

			case PDO::PARAM_NULL:
			case StatmentParameter::NIL :
				return PDO::PARAM_NULL;

			case PDO::PARAM_STR:
			case StatmentParameter::STR :
			case StatmentParameter::DATE :
			case StatmentParameter::LOB :
			case StatmentParameter::FLOAT :
				return PDO::PARAM_STR;
		}
		return PDO::PARAM_STR;
	}

	/**
	 * @param string|integer $mode
	 * @return integer
	 */
	private function getStatmentFetchMode($mode)
	{
		switch ($mode)
		{
			case PDO::FETCH_NUM:
			case self::FETCH_NUM :
				return PDO::FETCH_NUM;

			case PDO::FETCH_COLUMN:
			case self::FETCH_COLUMN :
				return PDO::FETCH_COLUMN;
		}
		return PDO::FETCH_ASSOC;
	}

	public function __destruct()
	{
		$this->close();
	}
}

class StatmentParameter
{
	const NIL = 'NIL';
	const INT = 'INT';
	const DATE = 'DATE';
	const STR = 'STR';
	const LOB = 'LOB';
	const FLOAT = 'FLOAT';

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param string $type
	 */
	public function __construct($name, $value = null, $type = self::STR)
	{
		$this->name = $name;
		$this->value = $value;
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}
}

<?php
class f_MongoProvider
{
	private $databaseName = null;
	private $writeConnectionString = null;
	
	private $mongoInstance = null;
	private $mongoDatabase = null;
	
	public function __construct($config)
	{
		if (isset($config["serversRead"]))
		{
			$hosts = implode(",", $config["serversRead"]);
			MongoCursor::$slaveOkay = true;
			$writehosts = implode(",", $config["serversWrite"]);
		}
		else
		{
			$hosts = implode(",", $config["serversWrite"]);
			$writehosts = null;
		}
		$this->databaseName = $config["database"]["name"];
		
		
		//"mongodb://[username:password@]host1[:port1][,host2[:port2:],...]/db";

		if (isset($config["authentication"]))
		{
			$authentication = $config["authentication"]["username"].':'.$config["authentication"]["password"].'@';
			$database = '/' . $this->databaseName;
		}
		else
		{
			$authentication = '';
			$database = '';
		}
		
		$connectionString = "mongodb://" . $authentication . $hosts . $database;
		if ($writehosts)
		{
			$this->writeConnectionString = "mongodb://" . $authentication . $writehosts . $database;
		}
		try
		{
			/*if ($config["modeCluster"] == "true")
			{
				self::$mongoInstance = new Mongo($connectionString, array("replicaSet" => true));
			}
			else 
			{*/
				$this->mongoInstance = new Mongo($connectionString, array("persist" => "mongo"));
			//}
			$this->mongoDatabase = $this->mongoInstance->selectDB($this->databaseName);
		}
		catch (MongoConnnectionException $e)
		{
			Framework::exception($e);
			$this->mongoInstance = null;
			$this->mongoDatabase = null;
		}
	}
	
	/**
	 * @return Boolean
	 */
	public function isAvailable()
	{
		return ($this->mongoDatabase !== null);
	}
	
	public function close()
	{
		if ($this->isAvailable())
		{
			$this->mongoInstance->close();
			$this->mongoDatabase = null;
			$this->mongoInstance = null;
		}
	}
	
	/**
	 * @param string $name
	 * @return MongoCollection
	 */
	public function getCollection($name, $write = false)
	{
		if ($write && $this->writeConnectionString)
		{
			$this->connectInWriteMode();
		}	
		if ($this->isAvailable())
		{
			return $this->mongoDatabase->selectCollection($name);
		}
		throw new Exception("MongoDatabase is not available.");		
	}
	
	/**
	 * @param string $name
	 * @return MongoDB
	 */
	public function getDatabase($name, $write = false)
	{
		if ($write && $this->writeConnectionString)
		{
			$this->connectInWriteMode();
		}	
		if ($this->isAvailable())
		{
			return $this->mongoInstance->selectDB($name);
		}
		throw new Exception("MongoDatabase is not available.");
	}
	
	/**
	 * @return Mongo
	 */
	public function getMongoInstance()
	{	
		if ($this->isAvailable())
		{
			return $this->mongoInstance;
		}
		throw new Exception("MongoInstance is not available.");
	}
	
	private function connectInWriteMode()
	{
		$connectionString = $this->writeConnectionString;
		$this->writeConnectionString = null;
			
		try
		{
			$this->mongoInstance = new Mongo($connectionString, array("persist" => "mongo"));
			$this->mongoDatabase = $this->mongoInstance->selectDB($this->databaseName);
		}
		catch (MongoConnnectionException $e)
		{
			Framework::exception($e);
			$this->mongoDatabase = null;
			$this->mongoInstance = null;
		}
	}
}
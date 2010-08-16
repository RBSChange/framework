<?php
class f_MongoProvider
{
	private static $instance = null;
	private static $mongoInstance = null;
	private static $mongoInstanceWrite = null;
	private static $mongoDatabase = null;
	private static $mongoDatabaseWrite = null;
	private static $writeMode = false;
	private static $readConnectionClosed = false;
	
	protected function __construct()
	{
		$connectionString = null;
		$config = Framework::getConfiguration("mongoDB");
		
		if (isset($config["authentication"]["username"]) && isset($config["authentication"]["password"]) && 
			$config["authentication"]["username"] !== '' && $config["authentication"]["password"] !== '')
		{
			$connectionString .= $config["authentication"]["username"].':'.$config["authentication"]["password"].'@';
		}
		
		if ($config["readWriteMode"] == "true")
		{
			self::$writeMode = true;
			MongoCursor::$slaveOkay = true;
			$connectionString .= implode(",", $config["serversRead"]);
		}
		else 
		{
			$connectionString .= implode(",", $config["serversWrite"]);
		}
		
		if ($connectionString != null)
		{
			$connectionString = "mongodb://".$connectionString;
		}
		
		try
		{
			/*if ($config["modeCluster"] == "true")
			{
				self::$mongoInstance = new Mongo($connectionString, array("replicaSet" => true));
			}
			else 
			{*/
				self::$mongoInstance = new Mongo($connectionString);
			//}
			self::$mongoDatabase = self::$mongoInstance->$config["database"]["name"];
		}
		catch (MongoConnnectionException $e)
		{
			Framework::exception($e);
		}
	}

	/**
	 * @return f_MongoProvider
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new f_MongoProvider();
		}
		return self::$instance;
	}
	
	/**
	 * @return MongoDB
	 */
	public function getMongo()
	{
		return self::$mongoDatabase;
	}
	
	/**
	 * @return MongoDB
	 */
	public function getWriteMongo()
	{
		$this->connectInWriteMode();
		return (self::$mongoDatabaseWrite !== null) ? self::$mongoDatabaseWrite : self::$mongoDatabase;
	}
	
	public function closeReadConnection()
	{
		if (!self::$readConnectionClosed && self::$writeMode)
		{
			self::$mongoInstance->close();
			self::$mongoInstance = null;
			self::$mongoDatabase = null;
			if (self::$mongoDatabaseWrite !== null)
			{
				self::$mongoDatabase = self::$mongoDatabaseWrite;
			}
		}
		self::$readConnectionClosed = true;
		return $this;
	}
	
	protected function connectInWriteMode()
	{
		if (self::$writeMode && self::$mongoInstanceWrite === null)
		{
			$connectionString = null;
			$config = Framework::getConfiguration("mongoDB");
			
			if (isset($config["authentication"]["username"]) && isset($config["authentication"]["password"]) && 
				$config["authentication"]["username"] !== '' && $config["authentication"]["password"] !== '')
			{
				$connectionString .= $config["authentication"]["username"].':'.$config["authentication"]["password"].'@';
			}
			
			$connectionString .= implode(",", $config["serversWrite"]);
			
			if ($connectionString != null)
			{
				$connectionString = "mongodb://".$connectionString;
			}
			
			try
			{
				/*if ($config["modeCluster"] == "true")
				{
					self::$mongoInstance = new Mongo($connectionString, array("replicaSet" => true));
				}
				else 
				{*/
					self::$mongoInstanceWrite = new Mongo($connectionString);
				//}
				self::$mongoDatabaseWrite = self::$mongoInstanceWrite->$config["database"]["name"];
			}
			catch (MongoConnnectionException $e)
			{
				Framework::exception($e);
			}
			if (self::$readConnectionClosed)
			{
				self::$mongoDatabase = self::$mongoDatabaseWrite;
			}
		}
	}
	
	public function __destruct()
	{
		if (self::$mongoInstance !== null)
		{
			self::$mongoInstance->close();
		}
		if (self::$mongoInstanceWrite !== null)
		{
			self::$mongoInstanceWrite->close();
		}
	}
}
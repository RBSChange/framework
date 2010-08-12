<?php
class f_MongoProvider
{
	private static $instance = null;
	private static $mongoInstanceRead = null;
	private static $mongoInstanceWrite = null;
	private static $mongoDatabase = null;
	private static $mongoDatabaseWrite = null;
	private static $writeMode = false;
	private static $readConnectionClosed = false;
	
	protected function __construct()
	{
		$connectionString = null;
		$config = Framework::getConfiguration("mongoDB");
		
		if ($config["readWriteMode"])
		{
			self::$writeMode = true;
		}
		
		if (isset($config["authentication"]["username"]) && isset($config["authentication"]["password"]) && 
			$config["authentication"]["username"] !== '' && $config["authentication"]["password"] !== '')
		{
			$connectionString .= $config["authentication"]["username"].':'.$config["authentication"]["password"].'@';
		}
		
		$connectionString .= implode(",", $config["serversRead"]);
		
		if ($connectionString != null)
		{
			$connectionString = "mongodb://".$connectionString;
		}
		
		try
		{
			/*if ($config["modeCluster"] && false)
			{
				self::$mongoInstance = new Mongo($connectionString, array("replicaSet" => true));
			}
			else 
			{*/
				self::$mongoInstanceRead = new Mongo($connectionString);
			//}
			self::$mongoDatabase = self::$mongoInstanceRead->$config["database"]["name"];
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
	 * @param Boolean $writeMode
	 * @return MongoDB
	 */
	public function getMongo($writeMode = false)
	{
		if ($writeMode && self::$writeMode)
		{
			$this->connectInWriteMode();
			return self::$mongoDatabaseWrite;
		}
		return self::$mongoDatabase;
	}
	
	public function closeReadConnection()
	{
		if (!self::$readConnectionClosed && self::$writeMode)
		{
			self::$mongoInstanceRead->close();
			self::$mongoInstanceRead = null;
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
		if (self::$mongoInstanceWrite === null)
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
				/*if ($config["modeCluster"] && false)
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
		if (self::$mongoInstanceRead !== null)
		{
			self::$mongoInstanceRead->close();
		}
		if (self::$mongoInstanceWrite !== null)
		{
			self::$mongoInstanceWrite->close();
		}
	}
}
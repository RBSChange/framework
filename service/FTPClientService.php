<?php
/**
 * @method f_DataCacheService getInstance()
 */
class f_FTPClientService extends change_BaseService
{
	/**
	 * @return f_FTPClient
	 * @param String $host
	 * @param Integer $port
	 */
	public function getNewClient($host, $port = 21)
	{
		return new f_FTPClient($host, $port);
	}
	
	/**
	 * @param String $localPath
	 * @param String $fullRemotePath "ftp://username:password@server:port/path"
	 */
	public function put($localPath, $fullRemotePath)
	{
		$matches = null;
		if (!preg_match('#ftp://([^\:@/]+)(\:([^\:@/]+))?@([^\:@/]+)(\:([0-9]+))?/(.+)#', $fullRemotePath, $matches))
		{
			throw new Exception("Invalid format for fullRemotePath");
		}
		$username = urldecode($matches[1]);
		$password = urldecode($matches[3]);
		$host = $matches[4];
		$port = $matches[6];
		$remotePath = $matches[7];
		
		$client = $this->getNewClient($host, $port);
		$client->login($username, $password);
		$client->put($localPath, $remotePath);
		$client->close();
	}
}

class f_FTPClient
{
	private $port;
	private $host;
	private $connectionId;
	private $username;
	private $password;
	private $logged = false;
	private $currentDir;
	
	/**
	 * @param String $host
	 * @param Integer $port
	 */
	public function __construct($host, $port = 21)
	{
		$this->host = $host;
		$this->port = $port;
	}
	
	/**
	 * Close ftp connection if needed
	 */
	public function close()
	{
		if ($this->connectionId !== null)
		{
			ftp_close($this->connectionId);
			$this->connectionId = null;
			$this->logged = false;
		}
	}
	
	/**
	 * @param String $username
	 * @param String $password
	 */
	public function login($username, $password)
	{
		if (!$this->logged)
		{
			$this->connect();
			$login_result = ftp_login($this->connectionId, $username, $password);
			if (!$login_result)
			{
				$this->close();
				throw new IOException("Could not log in with user (".$username."@".$this->host.":".$this->port.")");
			}
			if (!ftp_pasv($this->connectionId, true))
			{
				$this->close();
				throw new IOException("Could not turn on passive mode (".$this->host.":".$this->port.")");
			}
			$this->logged = true;
			$this->username = $username;
			$this->password = $password;
		}
	}
	
	/**
	 * @param String $localPath
	 * @param String $remotePath
	 */
	public function put($localPath, $remotePath)
	{
		$this->checkLogged();
		if (!ftp_put($this->connectionId, $remotePath, $localPath, FTP_BINARY))
		{
			throw new IOException("Could not write $localPath to ".$remotePath." (".$this->username."@".$this->host.":".$this->port.")");
		}
	}
	
	/**
	 * @param String $folder
	 * @return String[]
	 */
	public function ls($folder = ".")
	{
		$this->checkLogged();
		return ftp_nlist($this->connectionId, $folder);
	}
	
	/**
	 * @param String $folder
	 */
	public function chdir($folder) 
	{
		$this->checkLogged();
		if (!ftp_chdir($this->connectionId, $folder))
		{
			throw new Exception("Could not chdir to $folder");
		}
		$this->currentDir = null;
	}
	
	/**
	 * @return String
	 */
	public function getwd()
	{
		$this->checkLogged();
		if ($this->currentDir === null)
		{
			$pwd = ftp_pwd($this->connectionId); 
			if ($pwd === false)
			{
				throw new Exception("Could not get working directory"); 
			}
			$this->currentDir = $pwd;
		}
		return $this->currentDir;
	}
	
	/**
	 * @param String $remotePath
	 * @param String $localPath
	 */
	public function get($remotePath, $localPath)
	{
		$this->checkLogged();
		$fp = fopen($localPath, 'w');
		if ($fp === false)
		{
			throw new Exception("Could not open $localPath for writing");
		}
		if (ftp_fget($this->connectionId, $fp, $remotePath, FTP_BINARY) === false)
		{
			throw new Exception("Could not get $remotePath");
		}
		fclose($fp);
	}
	
	// protected content
	
	protected function checkLogged()
	{
		if (!$this->logged)
		{
			throw new Exception("You must first login");
		}
	}

	protected function connect()
	{
		if ($this->connectionId === null)
		{
			$connectionId = ftp_connect($this->host, $this->port);
			if (!$connectionId)
			{
				throw new IOException("Could not connect to ".$this->host.":".$this->port);
			}
			$this->connectionId = $connectionId;	
		}
	}
}

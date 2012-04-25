<?php
/**
 * @deprecated
 */
class UrlPDFService extends BaseService 
{
	/**
	 * @var Integer or String cache life time, in seconds, or in english (will be transformed with strtotime() function)
	 */
	private $cache_life_time = 600;

	/**
	 * @var String time format, define format for date() function to manage cache life time
	 */
	private $time_format = 'YmdHis';

	/**
	 * @var Integer socket timeout in seconds, default 60 seconds
	 */
	private $timeout = 60;

	/**
	 * @var String remote server ip
	 */
	private $server_ip = "10.255.2.150";

	/**
	 * @var Integer remote server port
	 */
	private $server_port = 20204;

	/**
	 * @var String user for xml2pdfd
	 */
	private $user;

	/**
	 * @var String pwd, user's password for xml2pdfd
	 */
	private $pwd;

	/**
	 * @var String cust, name of customer
	 */
	private $cust;

	/**
	 * @var string cache path (must end with '/' character)
	 */
	private $cache_path = '/tmp/pdf/';

	/**
	 * @var String default path cache, use to know if cache path as changed, so return only file instead of full path file
	 */
	private $default_cache_path;

	/**
	 * @var String ext, extention of filename
	 */
	private $ext = '.pdf';

	/**
	 * Password to lock pdf
	 *
	 * @var String pdf pwd
	 */
	private $pdf_pwd;

	/**
	 * Option to allow or not to print pdf, default allowed
	 *
	 * @var Boolean
	 */
	private $opt_print = true;

	/**
	 * Option to allow or not to copy pdf, default allowed
	 *
	 * @var Boolean
	 */
	private $opt_copy = true;

	/**
	 * Option to allow or not to annote pdf, default allowed
	 *
	 * @var Boolean
	 */
	private $opt_annon = true;

	/**
	 * Option to allow or not to modify pdf, default allowed
	 *
	 * @var Boolean
	 */
	private $opt_modif = true;

	/**
	 * @var opt zip, option for compression, between 1 and 5, default 3
	 */
	private $opt_zip = 3;


	/**
	 * @var Boolean force html conversion for prince
	 */
	private $force_html = false;


	/**
	 * The singleton instance 
	 * @var UrlPDFService
	 */
	private static $instance = null;


	/**
	 * @var Boolean clearPageCache to delete cache of url before generate pdf version
	 */
	private $clear_page_cache = false;

	/**
	 * @return UrlPDFService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			$className = get_class();
			self::$instance = new $className();
		}
		return self::$instance;
	}
	
	/**
	 * Define a password for connection to pdf server
	 *
	 * @param String $password
	 * @return UrlPDFService
	 */
	public function setPasswordConnection($password)
	{
		$this->pwd	= $password;
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Password connection : ".$this->pwd);
		}
		return $this;
	}
	
	/**
	 * Define a user for connection to pdf server
	 *
	 * @param String $user
	 * @return UrlPDFService
	 */
	public function setUserConnection($user)
	{
		$this->user	= $user;
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] User connection : ".$this->user);
		}
		return $this;
	}
	
	/**
	 * Define a customer for connection to pdf server
	 *
	 * @param String $cust
	 * @return UrlPDFService
	 */
	public function setCustomerConnection($cust)
	{
		$this->cust	= $cust;
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] User connection : ".$this->cust);
		}
		return $this;
	}
	
	/**
	 * Constructor, define parameters used by xml2pdfd
	 */
	public function __construct()
	{
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] ----- Bench ----- Starttime : ".date('Y-m-d H:i:s '));
		}
		
		$this->default_cache_path = $this->cache_path;
	}

	/**
	 * Set clear cache of asked url
	 */
	public function setClearPageCache()
	{
		$this->clear_page_cache = true;
	}

	/**
	 * Delete cache only for asked url
	 *
	 * @param Srting $pageID md5 of url
	 */
	private function clearCache($pageID)
	{
		$allFiles = glob($this->cache_path.$pageID.$this->ext.'*');
		foreach ($allFiles as $oneFile)
		{
			@unlink($oneFile);
		}
	}


	/**
	 * @TODO : ne récupérer que si la signature du fichier est différente de la version locale ?
	 * @TODO : vérifier la signature entre le fichié distant et celui transféré ?
	 * @param String $pageURL url to transform in pdf
	 * @return String the local pdf filename, without cache path if defined
	 * @throws Exception if remote pdf not created or local cache not created
	 */
	public function getPDF($pageURL)
	{
		$pageURL = str_replace('&amp;','&',$pageURL);

		$pageID = md5($pageURL);

		if($this->clear_page_cache == true)
		{
			if(Framework::isDebugEnabled())
			{
				Framework::debug("[PDF] Clear pdf caches for url : ".$pageURL);
			}
			$this->clearCache($pageID);
		}

		if($this->isCacheAvailable($pageID))
		{

			if(Framework::isDebugEnabled())
			{

				Framework::debug("[PDF] Retrun PDF cache found for pageid : ".$pageID);
				Framework::debug("[PDF] ----- Bench ----- Endtime : ".date('Y-m-d H:i:s '));
			}

			if($this->cache_path == $this->default_cache_path)
			{
				return $this->cache_path.$pageID.$this->ext;
			}
			else
			{
				return $pageID.$this->ext;
			}
		}
		else
		{

			try
			{
				$remotePdfUrl = $this->createPdfFile($pageURL);
			}
			catch (Exception $e)
			{
				// pb de création de pdf ...
				Framework::exception($e);
				throw $e;
			}
			if(Framework::isDebugEnabled())
			{
				Framework::debug("[PDF] Remote PDF file is created.");
			}
			try
			{
				$pdfFileName = $this->putInCache($pageID,$remotePdfUrl);
			}
			catch (Exception $e)
			{
				Framework::exception($e);
				throw $e;
			}
			if(Framework::isDebugEnabled())
			{
				Framework::debug("[PDF] Return PDF local cached file : ".$pdfFileName);
				Framework::debug("[PDF] ----- Bench ----- Endtime : ".date('Y-m-d H:i:s '));
			}

			if($this->cache_path == $this->default_cache_path)
			{
				return $this->cache_path.$pdfFileName;
			}
			else
			{
				return $pdfFileName;
			}
		}
	}

	/**
	 * Check if cache exist for pageID and if cache is still valid
	 *
	 * @param Integer $pageID , id of page to download as pdf
	 * @return boolean true if file is in cache and still valid, false otherwise
	 */
	private function isCacheAvailable($pageID)
	{
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Start search cached pdf for pageid : ".$pageID);
		}
		$cacheOk = false;
		if(file_exists($this->cache_path.$pageID.$this->ext))
		{
			if(Framework::isDebugEnabled())
			{
				Framework::debug("[PDF] PDF cache file exists for pageid : ".$pageID);
			}
			// search all cached file for the page
			$filesOfPage = glob($this->cache_path.$pageID.$this->ext."*");
			foreach ($filesOfPage as $filename)
			{
				if(strlen($filename)>strlen($this->cache_path.$pageID.$this->ext))
				{
					list(,,$expireTime) = explode('.',$filename);
					$currentTime = date($this->time_format);

					if($currentTime > $expireTime)
					{
						// cache expired, try to delete unuseful cache
						if(Framework::isDebugEnabled())
						{
							Framework::debug("[PDF] Found old cache (".$expireTime.") for pageid : ".$pageID);
						}
						@unlink($this->cache_path.$filename);
					}
					else
					{
						// cache file has not expired, so assume pdf cache file is ok
						if(Framework::isDebugEnabled())
						{
							Framework::debug("[PDF] Found valid cache (".$expireTime.") for pageid : ".$pageID);
						}
						$cacheOk = true;
					}
				}
			}
			if(!$cacheOk)
			{
				// if cache is not ok, it can be removed
				if(Framework::isDebugEnabled())
				{
					Framework::debug("[PDF] Found cached pdf but no valid cache for pageid : ".$pageID);
				}
				@unlink($this->cache_path.$pageID.$this->ext);
			}
		}
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] End of search cached pdf for pageid : ".$pageID);
		}
		return $cacheOk;
	}

	/**
	 * Ask xml2pdfd on remote server to create pdf file and return path file
	 *
	 * @param Srting $pageURL url to convert in pdf
	 * @return String url to remote pdf file
	 * @throws Exception with message if any errors occured
	 */
	private function createPdfFile($pageURL)
	{

		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] ----- Bench Create PDF ----- Starttime : ".date('Y-m-d H:i:s '));
			Framework::debug("[PDF] Start to create PDF file for url : ".$pageURL);
		}

		$socket = new Net_Socket();

		if(!$socket->connect($this->server_ip,$this->server_port,true,$this->timeout))
		{
			$msg = "[PDF] Can't connect to remote server : [IP:".$this->server_ip." - PORT:".$this->server_port."]";
			if(Framework::isFatalEnabled())
			{

				Framework::fatal($msg);
			}
			$socket->disconnect();
			throw new Exception($msg);
		}

		$socket->readLine();
		$socket->readLine();

		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Connected to remote server.");
		}

		$socket->writeLine("cred ".$this->user.":".$this->pwd);
		$response = $socket->readLine();

		if(trim($response)!='OK')
		{
			$msg = "[PDF] Can't login to server : [user:".$this->user." - pwd:".$this->pwd."]";
			if(Framework::isFatalEnabled())
			{
				Framework::fatal($msg);
			}
			$socket->disconnect();
			throw new Exception($msg);
		}

		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Authenticated to remote server.");
		}

		$socket->writeLine("cust ".$this->cust);
		$response = $socket->readLine();
		if(trim($response)!='OK')
		{
			$msg = "[PDF] Can't set customer : [customer:".$this->cust."]";
			if(Framework::isFatalEnabled())
			{
				Framework::fatal($msg);
			}
			$socket->disconnect();
			throw new Exception($msg);
		}

		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Customer set to remote server.");
		}

		// pdf options start
		if(!empty($this->pdf_pwd))
		{
			$socket->writeLine("pdfpass ".$this->pdf_pwd);
			$response = $socket->readLine();
			if(trim($response)!='OK')
			{

				if(Framework::isErrorEnabled())
				{
					Framework::error("[PDF] Can't set pdf pwd : [pdf_pwd:".$this->pdf_pwd."]");
				}
			}

			if(Framework::isDebugEnabled())
			{
				Framework::debug("[PDF] Password set to lock pdf.");
			}
		}

		if($this->opt_print == false)
		{
			$socket->writeLine("pdfnoprint ".(int)$this->opt_print);
			$response = $socket->readLine();
			if(trim($response)!='OK')
			{
				if(Framework::isErrorEnabled())
				{
					Framework::error("[PDF] Can't disable pdf print");
				}
			}
			if(Framework::isDebugEnabled())
			{
				Framework::debug("[PDF] Print option is disabled.");
			}
		}

		if($this->opt_copy == false)
		{
			$socket->writeLine("pdfnocopy ".(int)$this->opt_copy);
			$response = $socket->readLine();
			if(trim($response)!='OK')
			{
				if(Framework::isErrorEnabled())
				{
					Framework::error("[PDF] Can't disable pdf copy");
				}
			}
			if(Framework::isDebugEnabled())
			{
				Framework::debug("[PDF] Copy option is disabled.");
			}
		}

		if($this->opt_annon == false)
		{
			$socket->writeLine("pdfnoannon ".(int)$this->opt_annon);
			$response = $socket->readLine();
			if(trim($response)!='OK')
			{
				if(Framework::isErrorEnabled())
				{
					Framework::error("[PDF] Can't disable pdf annotation");
				}
			}
			if(Framework::isDebugEnabled())
			{
				Framework::debug("[PDF] Annotation option is disabled.");
			}
		}

		if($this->opt_modif == false)
		{
			$socket->writeLine("pdfnomodif ".(int)$this->opt_modif);
			$response = $socket->readLine();
			if(trim($response)!='OK')
			{
				if(Framework::isErrorEnabled())
				{
					Framework::error("[PDF] Can't disable pdf modification");
				}
			}
			if(Framework::isDebugEnabled())
			{
				Framework::debug("[PDF] Modification option is disabled.");
			}
		}

		$socket->writeLine("comp ".$this->opt_zip);
		$response = $socket->readLine();
		if(trim($response)!='OK')
		{
			if(Framework::isErrorEnabled())
			{
				Framework::error("[PDF] Can't set comporession : [COMPRESSION:".$this->opt_zip."]");
			}
		}
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Compression set to ".$this->opt_zip);
		}
		// pdf options end

		// define minimum cache time on remote server
		$socket->writeLine("ret 1");
		$socket->readLine();

		if($this->force_html == true)
		{
			$socket->writeLine("proto html");
			$response = $socket->readLine();
			if(trim($response)!='OK')
			{
				if(Framework::isErrorEnabled())
				{
					Framework::error("[PDF] Can't force html analyse instead of xhtml");
				}
			}
		}

		$socket->writeLine("print ".$pageURL);
		$remotepdfpath = $socket->readLine();

		if(substr(trim($remotepdfpath),-(strlen($this->ext)))!=$this->ext)
		{

			$msg = "[PDF] No pdf url received for page url : ".$pageURL;
			if(Framework::isFatalEnabled())
			{

				Framework::fatal($msg);
			}

			$socket->disconnect();
			throw new Exception($msg);

		}

		$response = $socket->readLine();

		if(trim($response)!='OK')
		{

			$msg = "[PDF] Url received but pdf creation failed";
			if(Framework::isFatalEnabled())
			{
				Framework::fatal($msg);
			}

			$socket->disconnect();
			throw new Exception($msg);
		}
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] PDF url on remote server received (".$remotepdfpath.")");
		}

		$socket->writeLine("quit");
		$socket->disconnect();

		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Disconnected for remote server.");
			Framework::debug("[PDF] ----- Bench Create PDF ----- Endtime : ".date('Y-m-d H:i:s '));
		}

		return $remotepdfpath;
	}

	/**
	 * Read remote file and save data to local cache, create "cache file time" to manage cache
	 *
	 * @param Integer $pageID , pdf cache file will contain this id in its name
	 * @param Srting $remotePdfFile, path of pdf file on remote server
	 * @return Srting path of new local cached pdf file
	 * @throws Exception if any problems occurs when reading or writing pdf
	 */
	private function putInCache($pageID, $remotePdfFile)
	{
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] ----- Bench Put PDF in cache ----- Starttime : ".date('Y-m-d H:i:s '));
			Framework::debug("[PDF] Start to put remote pdf (".$remotePdfFile.") in local cache");
		}
		// remove all cache for the page
		$cachedFiles = glob($this->cache_path.$pageID.$this->ext."*");
		foreach ($cachedFiles as $fileToDelete)
		{
			if(Framework::isDebugEnabled())
			{
				Framework::debug("[PDF] Remove old cache file for pageid : ".$pageID);
			}
			@unlink($fileToDelete);
		}

		if(is_dir($this->cache_path)===false)
		{
			if(mkdir($this->cache_path)===false)
			{
				$msg = "[PDF] Can't create cache directory [".$this->cache_path."]";
				if(Framework::isFatalEnabled())
				{
					Framework::fatal($msg);
				}
				throw new Exception($msg);
			}
		}

		// get remote file
		if( ($sourceFileHandler = @fopen("http://".$this->server_ip."/".$remotePdfFile,"rb")) === false )
		{
			$msg = "[PDF] Can't read remote pdf file [http://".$this->server_ip."/".$remotePdfFile."]";
			if(Framework::isFatalEnabled())
			{
				Framework::fatal($msg);
			}
			throw new Exception($msg);
		}
		if( ($finalCacheFileHandler = @fopen($this->cache_path.$pageID.$this->ext,"wb")) === false )
		{
			$msg = "[PDF] Can't open cache file [".$this->cache_path.$pageID.$this->ext."]";
			if(Framework::isFatalEnabled())
			{
				Framework::fatal($msg);
			}
			throw new Exception($msg);
		}

		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Remote file and local cache file opened for pageid : ".$pageID);
		}

		// write to local file
		while (!feof($sourceFileHandler))
		{
			if(fwrite($finalCacheFileHandler,fread($sourceFileHandler,1024)) == false)
			{
				$msg = "[PDF] Can't write cache file [".$this->cache_path.$pageID.$this->ext."]";
				if(Framework::isFatalEnabled())
				{
					Framework::fatal($msg);
				}
				throw new Exception($msg);
			}
		}

		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Local cache file written for pageid : ".$pageID);
		}

		fclose($sourceFileHandler);
		fclose($finalCacheFileHandler);

		// write file for cache life time
		$dateCache = date($this->time_format,time()+$this->cache_life_time);
		if(file_put_contents($this->cache_path.$pageID.$this->ext.".".$dateCache,"// Autogenerated file to define life time of cached file ".$pageID.$this->ext) === false)
		{
			$msg = "[PDF] Can't create life time cache file [".$this->cache_path.$pageID.$this->ext.".".$dateCache."]";
			if(Framework::isFatalEnabled())
			{
				Framework::fatal($msg);
			}
			throw new Exception($msg);
		}
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] File for cache time life created for pageid : ".$pageID);
		}

		if(file_exists($this->cache_path.$pageID.$this->ext))
		{
			if(Framework::isDebugEnabled())
			{
				Framework::debug("[PDF] ----- Bench Put PDF in cache ----- Endtime : ".date('Y-m-d H:i:s '));
			}
			return $pageID.$this->ext;
		}
		else
		{
			$msg = "[PDF] PDF cache file unavailable [".$this->cache_path.$pageID.$this->ext."]";
			if(Framework::isFatalEnabled())
			{
				Framework::fatal($msg);
			}
			throw new Exception($msg);
		}
	}

	/**
	 * Clear all files in cache directory
	 */
	public function clearAllCache()
	{
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Clear all pdf cache");
		}
		$allFiles = glob($this->cache_path.'*');
		foreach ($allFiles as $oneFile)
		{
			@unlink($oneFile);
		}
	}

	/**
	 * Define a password for the pdf
	 *
	 * @param String $password password to lock pdf
	 */
	public function setPassword($password)
	{
		$this->pdf_pwd	= $password;
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] PDF will be locked with pwd : ".$this->pdf_pwd);
		}
	}
	
	/**
	 * Disable print otpion in pdf
	 */
	public function disablePrint()
	{
		$this->opt_print = false;
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] PDF print is disabled");
		}
	}

	/**
	 * Disable copy otpion in pdf
	 */
	public function disableCopy()
	{
		$this->opt_copy = false;
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] PDF copy is disabled");
		}
	}

	/**
	 * Disable annotation otpion in pdf
	 */
	public function disableAnnon()
	{
		$this->opt_annon = false;
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] PDF annotation is disabled");
		}
	}

	/**
	 * Disable modification otpion in pdf
	 */
	public function disableModif()
	{
		$this->opt_modif = false;
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] PDF modification is disabled");
		}
	}

	/**
	 * Define pdf compression
	 *
	 * @param Integer $level 3 by default, must be between 1 and 5
	 */
	public function setCompressionLevel($level = 3)
	{
		if(is_integer($level))
		{
			$this->opt_zip = $level % 6;
			if($this->opt_zip == 0)
			{
				$this->opt_zip = 1;
			}
			if(Framework::isDebugEnabled())
			{
				Framework::debug("[PDF] PDF compression is set to : ".$this->opt_zip);
			}
		}
	}

	/**
	 * Force prince to take url as html and not xhtml
	 */
	public function forceHTMLFormat()
	{
		$this->force_html = true;
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Force source to be analysed as HTML and not XHTML");
		}
	}

	/**
	 * Define local path to cache pdf
	 *
	 * @param string $cache_path path to save pdf cache
	 */
	public function setCachePath($_path)
	{
		$this->cache_path = $_path . DIRECTORY_SEPARATOR;
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Cache path is set to : ".$this->cache_path);
		}
	}

	/**
	 * Define cache life time
	 *
	 * @param integer/string $cache_life_time optional (integer in second or string in english)
	 */
	public function setCacheLifeTime($_cache_life_time)
	{
		if(is_integer($_cache_life_time))
		{
			$this->cache_life_time = $_cache_life_time;
		}
		elseif(is_string($_cache_life_time))
		{
			$this->cache_life_time = strtotime($_cache_life_time,null);
		}
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Cache life time is set to : ".$this->cache_life_time." seconds.");
		}
	}

	/**
	 * Define ip address for xml2pdfd server
	 *
	 * @param string $server_ip ip where xml2pdfd is installed
	 */
	public function setServerIP($_ip)
	{
		if(ip2long($_ip) !== false || ip2long(gethostbyname($_ip)) !== false)
		{
			$this->server_ip = $_ip;
		}

		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Server IP is set to : ".$this->server_ip);
		}
	}

	/**
	 * Define port where xml2pdfd listen
	 *
	 * @param integer $server_port port where xml2pdfd listen
	 */
	public function setServerPort($_port)
	{
		if(is_integer($_port))
		{
			$this->server_port = $_port % 65536;
		}
		
		if(Framework::isDebugEnabled())
		{
			Framework::debug("[PDF] Server Port is set to : ".$this->server_port);
		}
	}

	/**
	 * Return an url to call the pdf convertion.
	 * Call action ConvertPdf on generic module
	 *
	 * @param $specificUrl url to convert instead of current url
	 * @return string
	 */
	public function getLink($specificUrl = null)
	{
		$currentUrl = null;
		
		if(!is_null($specificUrl))
		{
			$valid = new validation_UrlValidator();
			$errors = new validation_Errors();
			
			if($valid->validate($specificUrl, $errors))
			{
				$currentUrl = base64_encode($specificUrl);
			}
		}
		
		if(is_null($currentUrl))
		{			
			$currentUrl	= base64_encode( paginator_Url::getInstanceFromCurrentUrl()->getStringRepresentation());
		}
		
		return HttpController::getInstance()->genURL( null, array( 'url'=>$currentUrl, 'module'=>'generic', 'action'=>'ConvertPdf' ) );
	}
	
}
?>
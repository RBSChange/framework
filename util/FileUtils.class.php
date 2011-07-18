<?php
abstract class f_util_FileUtils
{
	const OVERRIDE = 2;
	const APPEND = 4;

	const LOCALE_PATH =  '&modules.media.download.';

	static public function isDirectoryWritable($filepath,$recurse=false)
	{

		if (is_dir($filepath)==false) throw new Exception("not a directory: $filepath");
		$result = @file_put_contents($filepath.'/write.test','this is a write test');
		@unlink($filepath.'/write.test');

		if ($result>0)
		{
			if ($dh = opendir($filepath)) 
			{
				while (($file = readdir($dh)) !== false) {
					if ($file!='..' && $file!='.' && $file!='.svn' && is_dir($filepath.'/'.$file)) self::isDirectoryWritable($filepath.'/'.$file,$recurse);
				}
				closedir($dh);
			}

			return true;
		}
		throw new Exception("directory not writable: $filepath");
	}
	
	/**
	 * @param string $path
	 * @return string
	 */
	static function normalizePath($path)
	{
		return (DIRECTORY_SEPARATOR === '/') ? $path : str_replace('/', DIRECTORY_SEPARATOR, $path);
	}
	
	/**
	 * @param string $path
	 * @return string
	 */
	static function isLink($filepath)
	{
		if (DIRECTORY_SEPARATOR === '/')
		{
			return is_link($filepath);
		}
		
		if (file_exists($filepath) && self::normalizePath($filepath) != readlink($filepath))
		{
			return true;
		}
		return false;
	}	
	
	/**
	 * "ln -s $linkTarget $linkPath"
	 * @param String $linkTarget
	 * @param String $linkPath
	 * @param Integer $options value in {self::OVERRIDE}
	 * @throws Exception if the target is not readable or the symlink creation failed
	 * @return Boolean true if link has to be created or updated
	 */
	static function symlink($linkTarget, $linkPath, $options = 0)
	{
		if (!is_readable($linkTarget))
		{
			throw new Exception("$linkTarget does not exist");
		}
		$isLink = self::isLink($linkPath);
		if (file_exists($linkPath) || $isLink)
		{
			if ($isLink && realpath(readlink($linkPath)) == realpath($linkTarget))
			{
				// nothing to do
				return false;
			}
			if ($options & self::OVERRIDE)
			{
				if (DIRECTORY_SEPARATOR === '/')
				{
					if (!unlink($linkPath) || !symlink($linkTarget, $linkPath))
					{
						throw new Exception("Could not create symlink $linkPath => $linkTarget");
					}
				}
				else
				{
					if (is_dir($linkPath))
					{
						if (!rmdir($linkPath) || !symlink($linkTarget, $linkPath))
						{
							throw new Exception("Could not create symlink $linkPath => $linkTarget (win)");
						}	
					}
					else
					{
						if (!unlink($linkPath) || !symlink($linkTarget, $linkPath))
						{
							throw new Exception("Could not create symlink $linkPath => $linkTarget (win)");
						}	
					}
				}
				return true;
			}
			else
			{
				throw new Exception("Could not create symlink on $linkPath location because file exists");
			}
		}
		if (!symlink($linkTarget, $linkPath))
		{
			throw new Exception("Could not create symlink $linkPath => $linkTarget");
		}
		return true;
	}

	/**
	 * Clean string to be acceptable for a filename
	 *
	 * @param String $string
	 * @return String
	 */
	public static function normalizeFilename($string)
	{
		$string = f_util_StringUtils::strip_accents($string);
		return preg_replace('=[ |:|\\|/|\?|>|<|*|"|\'|\|]=s','_', $string);
	}

	/**
	 * Clear the content of $path file.
	 * If $path does not exist, creates it
	 *
	 * @param String $path
	 */
	public static function clearFile($path)
	{
		self::unlink($path);
		touch($path);
	}

	/**
	 * Unlink the file specified by $filePath.
	 * @param String $filePath
	 * @return Boolean
	 */
	public static function unlink($filePath)
	{
		if (file_exists($filePath))
		{
			return unlink($filePath);
		}
		// The file does not exist? Well... I wanted to remove it, so that's OK
		// (but a message is logged).
		else if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__.": \"$filePath\" does not exist.");
		}
		return true;
	}

	/**
	 * @param String $pattern
	 * @param String $fromPath
	 * @return String[] the founded files as a "relative to $fromPath path" array
	 * @example f_util_FileUtils::find("*.php", util_File_Utils::buildWebeditPath("modules", "myModule"));
	 */
	static public function find($pattern, $fromPath)
	{
		if ($fromPath[count($fromPath) -1] !== DIRECTORY_SEPARATOR)
		{
			$fromPath .= DIRECTORY_SEPARATOR;
		}
		return self::rglob($pattern, 0, $fromPath);
	}

	/**
	 * @param string $pattern
	 * @param intgere $flags
	 * @param string $path
	 * @return string[]
	 */
	static private function rglob($pattern = '*', $flags = 0, $path = '')
	{
		$files = glob($path.$pattern, $flags);
		if (!is_array($files)){$files = array();}
		$paths = glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
		if (is_array($paths))
		{
			foreach ($paths as $path)
			{
				$files = array_merge($files, self::rglob($pattern, $flags, $path));
			}
		}
		return $files;
	}

	/**
	 * create dynamically a directory (and sub-directories) on filesystem
	 * @param string $directoryPath the directory to create
	 * @throws IOException
	 */
	static public function mkdir($directoryPath)
	{
		if (file_exists($directoryPath))
		{
			if (is_dir($directoryPath))
			{
				return true;
			}
			throw new IOException("$directoryPath already exists and is not a folder");
		}
		
		if (!@mkdir($directoryPath, 0777, true))
		{
			clearstatcache();
			if (!is_dir($directoryPath))
			{
				throw new IOException("Could not create $directoryPath");
			}
		}
		return true;
	}

	/**
	 * @example FileUtils::buildRelativePath('home', 'toto') returns 'home/toto'
	 * @return String the path builded using concatenated arguments
	 */
	public static function buildRelativePath()
	{
		$args = func_get_args();
		return join(DIRECTORY_SEPARATOR, $args);
	}

	/**
	 * @example FileUtils::buildPath('home', 'toto') returns 'home/toto'
	 * @example FileUtils::buildPath('/home/titi/tutu', 'toto') returns '/home/titi/tutu/toto'
	 * @return String the path builded using concatenated arguments
	 */
	public static function buildPath()
	{
		$args = func_get_args();
		return join(DIRECTORY_SEPARATOR, $args);
	}

	/**
	 * @example FileUtils::buildAbsolutePath('home', 'toto') returns '/home/toto'
	 * @example FileUtils::buildAbsolutePath('/home', 'toto') returns '/home/toto'
	 * @return String the path builded using concatenated arguments
	 */
	public static function buildAbsolutePath()
	{
		$args = func_get_args();
		return self::buildAbsolutePathFromArray($args);
	}

	/**
	 * @param array<String> $args
	 * @return String
	 */
	private static function buildAbsolutePathFromArray($args)
	{
		if (DIRECTORY_SEPARATOR !== '/' || f_util_StringUtils::beginsWith($args[0], DIRECTORY_SEPARATOR))
		{
			return join(DIRECTORY_SEPARATOR, $args);
		}
		return DIRECTORY_SEPARATOR.join(DIRECTORY_SEPARATOR, $args);
	}

	/**
	 * @return String
	 */
	static function buildRepositoryPath()
	{
		$args = func_get_args();
		array_unshift($args, LOCAL_REPOSITORY);
		return self::buildAbsolutePathFromArray($args);
	}

	/**
	 * @example FileUtils::buildWebeditPath('bin', 'tasks') returns WEBEDIT_HOME.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'tasks'
	 * @return String
	 */
	public static function buildWebeditPath()
	{
		$args = func_get_args();
		array_unshift($args, WEBEDIT_HOME);
		return self::buildAbsolutePathFromArray($args);
	}
		
	/**
	 * @example FileUtils::buildOverridePath('toto') returns PROJECT_OVERRIDE.DIRECTORY_SEPARATOR.'toto'
	 * @return String
	 */
	public static function buildOverridePath()
	{
		$args = func_get_args();
		array_unshift($args, PROJECT_OVERRIDE);
		return self::buildAbsolutePathFromArray($args);
	}

	/**
 	 * @example FileUtils::buildDocumentRootPath('toto') returns DOCUMENT_ROOT.DIRECTORY_SEPARATOR.'toto'
	 * @return String
	 */
	public static function buildDocumentRootPath()
	{
		$args = func_get_args();
		array_unshift($args, DOCUMENT_ROOT);
		return self::buildAbsolutePathFromArray($args);
	}
	
	/**
	 * @example FileUtils::buildCachePath('toto') returns AG_CACHE_DIR.DIRECTORY_SEPARATOR.'toto'
	 * @return String
	 */
	public static function buildCachePath()
	{
		$args = func_get_args();
		array_unshift($args, CHANGE_CACHE_DIR);
		return self::buildAbsolutePathFromArray($args);
	}

	/**
	 * @example FileUtils::buildCachePath('toto') returns AG_CACHE_DIR.DIRECTORY_SEPARATOR.'toto'
	 * @return String
	 */
	public static function buildChangeCachePath()
	{
		$args = func_get_args();
		array_unshift($args, CHANGE_CACHE_DIR);
		return self::buildAbsolutePathFromArray($args);
	}
	
	/**
	 * @example FileUtils::buildWebCachePath('toto') returns WEB_CACHE_DIR.DIRECTORY_SEPARATOR.'toto'
	 * @return String
	 */
	public static function buildWebCachePath()
	{
		$args = func_get_args();
		array_unshift($args, WEB_CACHE_DIR);
		return self::buildAbsolutePathFromArray($args);
	}

	/**
	 * @example FileUtils::buildChangeBuildPath('toto') returns CHANGE_BUILD_DIR.DIRECTORY_SEPARATOR.'toto'
	 * @return String
	 */
	public static function buildChangeBuildPath()
	{
		$args = func_get_args();
		array_unshift($args, CHANGE_BUILD_DIR);
		return self::buildAbsolutePathFromArray($args);
	}

	/**
	 * @example FileUtils::buildFrameworkPath('indexer.log') returns CHANGE_LOG_DIR.DIRECTORY_SEPARATOR.'indexer.log'
	 * @return String
	 */
	public static function buildLogPath()
	{
		$args = func_get_args();
		array_unshift($args, CHANGE_LOG_DIR);
		return self::buildAbsolutePathFromArray($args);
	}

	/**
	 * @example FileUtils::buildFrameworkPath('config', 'listeners.xml') returns FRAMEWORK_HOME.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'listeners.xml'
	 * @return String
	 */
	public static function buildFrameworkPath()
	{
		$args = func_get_args();
		array_unshift($args, FRAMEWORK_HOME);
		return self::buildAbsolutePathFromArray($args);
	}

	/**
	 * TODO: this method does NOT returns what it promise to do ...
	 * remove a directory (and sub-directories) on filesystem
	 * @param $directoryPath the directory to remove
	 * @return boolean FALSE if directory cannot be removed
	 */
	static public function rmdir($directoryPath, $onlyContent = false)
	{
		if (is_dir($directoryPath))
		{
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::KEY_AS_PATHNAME), RecursiveIteratorIterator::CHILD_FIRST) as $file => $info)
			{
				$fileName = $info->getFilename();
				if ($fileName === '.' || $fileName === '..')
				{
					continue;
				}
				if ($info->isFile() || self::isLink($info->getPathname()))
				{
					unlink($file);
				}
				else if ($info->isDir())
				{
					rmdir($file);
				}
			}
			if (!$onlyContent)
			{
				rmdir($directoryPath);
			}
		}
	}

	/**
	 * Get the path of the files contained in a directory
	 * @param String $dirPath
	 * @return String[]
	 */
	static public function getDirFiles($dirPath)
	{
		if (!is_dir($dirPath))
		{
			throw new Exception("$dirPath does not exists");
		}
		$files = array();
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::KEY_AS_PATHNAME), RecursiveIteratorIterator::SELF_FIRST) as $file => $info)
		{
			if (!$info->isDir())
			{
				$files[] = $file;
			}
		}
		return $files;
	}

	/**
	 * Clean a directory from its content : files and directories
	 * @param $directoryPath the directory to remove
	 */
	static public function cleanDir($directoryPath)
	{
		return self::rmdir($directoryPath, true);
	}

	/**
	 * This function is use to get the extension of a file.
	 * @param $filename : The name of the file we want to get extension.
	 * @param $includeDot : Facultative, precise if we want the dot on the extension return.
	 * @param $nb_ext : Facultative, 1 by default. Number of extension we must catch. For example a file named
	 * file.ext1.ext2.ext3. If $nb_ext==1, the program will return "ext3". If $nb_ext==2, the program will return
	 * ext2.ext3.
	 * If a file have only one extension and we ask two, the program will return ''. It's the same if the file
	 * dont have any extension.
	 * @return string : The extension of a filename.
	 */
	final static function getFileExtension($filename, $includeDot = false, $nb_ext = 1) {
		$final_ext = '';

		while ($nb_ext >= 1)
		{
			$current_ext = strrchr($filename, '.');

			if (empty($current_ext))
			{
				return ('');
			}

			$filename = mb_substr($filename, 0, -(mb_strlen($current_ext)));
			$final_ext = $current_ext . $final_ext;
			--$nb_ext;
		}

		if (!empty($final_ext) && ($includeDot == false))
		{
			$final_ext = substr($final_ext, 1);
		}

		return $final_ext;
	}

	public static final function getContentTypeFromFile($filename)
	{
		return self::getContentTypeFromExtension(self::getFileExtension($filename));

	}

	public static final function getContentTypeFromExtension($extension)
	{
		switch (strtolower($extension))
		{
			case 'flv': return 'video/x-flv';
			case 'png': return 'image/png';
			case 'gif': return 'image/gif';
			case 'jpg':
			case 'jpeg':
				return 'image/jpeg';
			case 'swf': return 'application/x-shockwave-flash';
			case 'pdf': return 'application/pdf';
			case 'ico': return 'image/x-icon';
			default:
				return 'application/octet-stream';
		}
	}

	/**
	 * This method create a temporary file and returns it's path.
	 * You can prefix the name of the temporary file
	 * @param string $prefix
	 * @param boolean $deleteOnExit
	 * @return string
	 */
	static public function getTmpFile($prefix = null, $deleteOnExit = false)
	{
		$prefix = (f_util_StringUtils::isEmpty($prefix)) ? 'change_' : $prefix;
		$filePath = tempnam(TMP_PATH, $prefix);
		if ($deleteOnExit)
		{
			self::registerTmpFileCleaner();
			self::$tmpFilesToDelete[] = $filePath;
		}
		return $filePath;
	}
	
	static protected function registerTmpFileCleaner()
	{
		if (!self::$tmpFilesCleanerRegistered)
		{
			register_shutdown_function(array("f_util_FileUtils", "cleanTmpFiles"));
			self::$tmpFilesCleanerRegistered = true;
		}
	}
	
	static public function cleanTmpFiles()
	{
		foreach (self::$tmpFilesToDelete as $tmpFile)
		{
			@unlink($tmpFile);
		}
	}
	
	private static $tmpFilesToDelete = array();
	private static $tmpFilesCleanerRegistered = false;

	/**
	 * This method returns a boolean value if delete succeed or not.
	 * Be carefull ; this function doesn't delete folders.
	 *
	 * @param  $filePath  Path of the file to delete or array of files to delete
	 * @return boolean    TRUE if delete succeed or file doesn't exists, otherwise FALSE
	 */
	static public function deleteTmpFile($filePath = null)
	{
		if (!is_string($filePath) && !is_array($filePath)) {
			throw new FrameworkException('deleteTmpFile_invalid_parmater');
		}
		if (empty($filePath)) {
			return TRUE;
		}

		if (is_string($filePath)) {
			$filePath = array($filePath);
		}

		$result = TRUE;
		foreach($filePath as $path) {
			if ($result == FALSE) {
				continue;
			}
			if (file_exists($path)) {
				if (is_dir($path)) {
					continue;
				}

				$result = unlink($path);
			}
		}
		return $result;
	}

	/**
	 * Clear the content of a directory
	 *
	 * @param string $dir Full directory path.
	 * @param array $extension Extensions of the files to clear
	 * @param array $except Names of the files to skip
	 */
	static public function clearDir($dir, $extension = array(), $except = array())
	{
		if (substr($dir, -1) != DIRECTORY_SEPARATOR)
		{
			$dir .= DIRECTORY_SEPARATOR;
		}
		if (is_dir($dir))
		{
			if ($dh = opendir($dir))
			{
				while (($file = readdir($dh)) !== false)
				{
					if (($file != '.')
					&& ($file != '..')
					&& is_file($dir . $file)
					&& !is_dir($dir . $file)
					&& is_writable($dir . $file))
					{
						if (!in_array($file, $except))
						{
							$fileExtension = strtolower(substr($file, strrpos($file, '.') + 1));
							if (empty($extension) || in_array($fileExtension, $extension))
							{
								@unlink($dir . $file);
							}
						}
					}
				}
				closedir($dh);
			}
		}
	}
	
	/**
	 * @param String $path
	 * @throws IOException on error
	 */
	static function touch($path, $time = null)
	{
		if ($time === null)
		{
			if (!touch($path))
			{
				throw new IOException('Could not touch '.$path);
			}
		}
		else
		{
			if (!touch($path, $time))
			{
				throw new IOException('Could not touch '.$path);
			}
		}
	}

	/**
	 * write data to file designed by $path
	 *
	 * @param String $path
	 * @param mixed $content
	 * @param Integer $options value in {self::OVERRIDE}
	 * @return Boolean if file was really written (could be false with no override option)
	 * @throws IOException on error
	 */
	public static function write($path, $content, $options = 0)
	{
		if ($path === null || $content === null)
		{
			throw new IllegalArgumentException('path and content', 'string');
		}

		if (file_exists($path) && !($options & self::OVERRIDE) && !($options & self::APPEND))
		{
			return false;
		}

		// file_put_contents doesn't work well with FTP protocol, so distinguish this case.
		if (substr($path, 0, 6) == 'ftp://')
		{
			return self::ftpWrite($path, $content, $options);
		}
		else
		{
			return self::fileWrite($path, $content, $options);
		}
	}

	/**
	 * write data to file designed by $path
	 *
	 * @param String $path
	 * @param mixed $content
	 * @param Integer $options value in {self::OVERRIDE}
	 * @return Boolean if file was really written (could be false with no override option)
	 * @throws IOException on error
	 */
	private static function fileWrite($path, $content, $options)
	{
		$flags = 0;
		if ($options & self::APPEND)
		{
			$flags += FILE_APPEND;
		}
		if (file_put_contents($path, $content, $flags) === false)
		{
			throw new IOException('Could not write data to '.$path);
		}

		return true;
	}

	/**
	 * write data to file designed by $path threw FTP protocol
	 *
	 * @param String $path matching the following format : "ftp://username:password@server:port/path".
	 * @param mixed $content
	 * @param Integer $options value in {self::OVERRIDE}
	 * @return Boolean if file was really written (could be false with no override option)
	 * @throws IOException on error
	 */
	private static function ftpWrite($path, $content, $options)
	{
		$temporaryFile = self::getTmpFile('temp-');
		self::fileWrite($temporaryFile, $content, 0);
		try
		{
			f_FTPClientService::getInstance()->put($temporaryFile, $path);
		}
		catch (Exception $e)
		{
			unlink($temporaryFile);
			throw $e;
		}
		unlink($temporaryFile);

		return true;
	}

	/**
	 * write data to file designed by $path and create the dirname of $path if not exists
	 *
	 * @param String $path
	 * @param mixed $content
	 * @throws IOException
	 */
	public static function writeAndCreateContainer($path, $content, $options = 0)
	{
		self::mkdir(dirname($path));
		self::write($path, $content, $options);
	}

	/**
	 * Copy files or directories
	 * @param String $from
	 * @param String $dest
	 * @param Integer $options value in {self::OVERRIDE}
	 * @param String[] $exclude patterns of file to exclude (mean something when $from is a directory)
	 */
	public static function cp($from, $dest, $options = 0, $exclude = null)
	{
		if (file_exists($dest))
		{
			if (!($options & self::OVERRIDE))
			{
				throw new Exception("File $dest already exists");
			}
			if (!($options & self::APPEND))
			{
				self::rmdir($dest);
				clearstatcache();
			}
		}

		if (is_file($from))
		{
			self::mkdir(dirname($dest));
			if (!copy($from, $dest))
			{
				throw new Exception("Could not copy $from to $dest");
			}
		}
		elseif (is_dir($from))
		{
			self::mkdir($dest);
			$fromLength = strlen($from);
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, RecursiveDirectoryIterator::KEY_AS_PATHNAME), RecursiveIteratorIterator::SELF_FIRST) as $file => $info)
			{
				$relFile = substr($file, $fromLength+1);
				$destFile = $dest."/".$relFile;
				if ($exclude !== null && count(array_intersect($exclude, explode(DIRECTORY_SEPARATOR, $relFile))) > 0)
				{
					// Excluded file or directory
					continue;
				}
				if (!($options & self::OVERRIDE) && file_exists($destFile))
				{
					throw new Exception("File $dest already exists");
				}
				//echo $destFile."\n";
				if ($info->isDir())
				{
					if (is_dir($destFile) || (self::isLink($destFile) && is_dir(readlink($destFile))))
					{
						continue;
					}
					if (!mkdir($destFile))
					{
						throw new Exception("Could not make $destFile dir");
					}
				}
				else
				{
					if (!copy($file, $destFile))
					{
						throw new Exception("Could not copy $file to $destFile");
					}
				}
			}
		}
	}

	/**
	 * @param String $path
	 * @return String the content of file[@path] = $path
	 * @throws FileNotFoundException
	 * @throws IOException
	 */
	public static function read($path)
	{
		// file_get_contents doesn't work well with FTP protocol, so distinguish this case.
		if (substr($path, 0, 6) == 'ftp://')
		{
			return self::ftpRead($path);
		}
		else
		{
			return self::fileRead($path);
		}
	}

	/**
	 * @param String $path
	 * @return String the content of file[@path] = $path
	 * @throws FileNotFoundException
	 * @throws IOException if file could not be read
	 */
	private static function fileRead($path)
	{
		if (!is_readable($path))
		{
			throw new FileNotFoundException($path);
		}
		$content = file_get_contents($path);
		if ($content === false)
		{
			throw new IOException("Error while reading $path");
		}
		return $content;
	}

	/**
	 * @param String $path
	 * @return String[] the content of file[@path] = $path
	 * @throws FileNotFoundException
	 * @throws IOException if file could not be read
	 */
	static function readArray($path)
	{
		if (!is_readable($path))
		{
			throw new FileNotFoundException($path);
		}
		$content = file($path, FILE_IGNORE_NEW_LINES);
		if ($content === false)
		{
			throw new IOException("Error while reading $path");
		}
		return $content;
	}

	/**
	 * @param String $path
	 * @return String the content of file[@path] = $path
	 * @throws IOException
	 */
	private static function ftpRead($path)
	{
		// Extract parameters.
		preg_match('#ftp://([^\:@/]+)(\:([^\:@/]+))?@([^\:@/]+)(\:([0-9]+))?/(.+)#', $path, $matches);
		$username = $matches[1];
		$password = $matches[3];
		$host = $matches[4];
		$port = $matches[6];
		$remotePath = $matches[7];

		// Open FTP connection.
		$connectionId = ftp_connect($host, $port);
		if (!$connectionId)
		{
			throw new IOException('Could not connect to '.$host.':'.$port);
		}
		$login_result = ftp_login($connectionId, $username, $password);
		if (!$login_result)
		{
			ftp_close($connectionId);
			throw new IOException('Could not log in with user '.$username);
		}

		// Activate passive mode.
		ftp_pasv($connectionId, true);

		// Send file.
		$temporaryFile = self::getTmpFile('temp-');
		$result = ftp_get($connectionId, $temporaryFile, $remotePath, FTP_ASCII);
		$fileContent = file_get_contents($temporaryFile);
		unlink($temporaryFile);

		// Close FTP connection.
		ftp_close($connectionId);

		// If the file was not correctly put, throw exception.
		if (!$result)
		{
			throw new IOException('Could not read data from '.$path);
		}
		return $fileContent;
	}

	/**
	 * append data to file designed by $path
	 *
	 * @param String $path
	 * @param mixed $content
	 * @throws IOException
	 */
	public static function append($path, $content)
	{
		if (file_put_contents($path, $content, FILE_APPEND) === false)
		{
			throw new IOException('Could not append data to '.$path);
		}
	}

	public static function getReadableFileSize($path, $lang = null)
	{
		if (!$lang)
		{
			$lang = RequestContext::getInstance()->getLang();
		}

		$fileSize = '';

		if (is_readable($path))
		{
			$size = filesize($path);
			$i = 0;
			$iec = array("b", "kb", "mb", "gb", "tb", "pb", "eb", "zb", "yb");
			while ( ($size / 1024) > 1)
			{
				$size = $size / 1024;
				$i++;
			}
			$fileSize = sprintf("%.1f %s", $size, f_Locale::translate(self::LOCALE_PATH .ucfirst($iec[$i]).'-acronym;', null, $lang));
		}

		return $fileSize;
	}

	public static function cleanFilename($fileName)
	{
		$fileName = f_util_StringUtils::strip_accents($fileName);
		$fileName = str_replace(' ', '_', $fileName);
		return $fileName;
	}

	/**
	 * @param String $file
	 * @param String|Integer $mode Cf. http://php.net/manual/en/function.chmod.php
	 * @param Boolean $recursive
	 * @param String|Integer $filesMode if recursive, you can specify a different mode than $mode for files
	 * @example chmod(..., "2775") or chmod (..., 02775)
	 */
	public static function chmod($file, $mode, $recursive = true, $filesMode = null)
	{
		if (!function_exists('posix_getuid'))
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' diasabled');
			}
			//Unable to execute this function
			return;
		}
		$uid = posix_getuid();
		if (is_string($mode))
		{
			$mode = intval("0".$mode, 8);
		}
		
		if ($filesMode === null)
		{
			$filesMode = $mode;	
		}
		elseif (is_string($filesMode))
		{
			$filesMode = intval("0".$filesMode, 8);
		}
		
		if (chmod($file, $mode) === false)
		{
			throw new Exception("Could not chmod $mode $file");
		}
		
		if ($recursive && is_dir($file))
		{
			//echo "Recursive chmod ".$this->file." ".$this->mode." ".$mode."\n";
			$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file), RecursiveIteratorIterator::SELF_FIRST);
			foreach ($dir as $fileInfo)
			{
				// http://bugs.php.net/bug.php?id=40548, fixed starting from 5.2.2: warning if broken link
				if ($fileInfo->getOwner() !== $uid)
				{
					if (!$fileInfo->isWritable() && !$fileInfo->isLink())
					{
						throw new Exception($fileInfo->getPathname()." is not writeable");
					}
				}
				else
				{
					if ($fileInfo->isDir())
					{
						 if (chmod($fileInfo->getPathname(), $mode) === false)
						 {
						 	throw new Exception("Could not chmod ".$fileInfo->getPathname());
						 }
					}
					elseif (chmod($fileInfo->getPathname(), $filesMode) === false)
					{
						throw new Exception("Could not chmod ".$fileInfo->getPathname());
					}
				}
			}
		}
	}

	/**
	 * @param String $file
	 * @param String $owner nullable
	 * @param String $group
	 * @param Boolean $recursive
	 */
	public static function chown($file, $owner, $group, $recursive = true)
	{
		if (!function_exists('posix_getuid'))
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' diasabled');
			}
			//Unable to execute this function
			return;
		}
		
		$uid = posix_getuid();
		if ($group !== null)
		{
			if (is_numeric($group))
			{
				$group = $group;
			}
			else
			{
				$groupInfo = posix_getgrnam($group);
				$group = $groupInfo["gid"];
			}
		}

		if ($owner !== null && chown($file, $owner) === false)
		{
			f_util_ProcessUtils::printBackTrace();
			throw new Exception("Could not chown $owner ".$file);
		}
		if ($group !== null)
		{
			if (chgrp($file, $group) === false)
			{
				throw new Exception("Could not chgrp $group ".$file);
			}
		}

		if ($recursive && is_dir($file))
		{
			$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file), RecursiveIteratorIterator::SELF_FIRST);
			foreach ($dir as $fileInfo)
			{
				if ($fileInfo->getOwner() !== $uid)
				{
					if (!$fileInfo->isWritable() && !$fileInfo->isLink())
					{
						throw new Exception($fileInfo->getPathname()." is not writeable");
					}
				}
				else
				{
					$pathName = $fileInfo->getPathname();
					if ($owner !== null && chown($pathName, $owner) === false)
					{
						throw new Exception("Could not chown ".$pathName);
					}
					if ($group !== null && $fileInfo->getGroup() !== $group)
					{
						$oldPerms = $fileInfo->getPerms();
						if (chgrp($pathName, $group) === false)
						{
							throw new Exception("Could not chgrp ".$pathName);
						}
					}
				}
			}
		}
	}
	
	// Deprecated
	
	/**
	 * @deprecated (will be removed in 4.0)
	 */
	public static function buildWebappPath()
	{
		$args = func_get_args();
		Framework::warn(__METHOD__);
		array_unshift($args, PROJECT_OVERRIDE);
		return self::buildAbsolutePathFromArray($args);
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use write, replace $override = true with FileUtils::OVERRIDE
	 */
	static public function saveFile($path, $content, $override = true)
	{
		if ($override)
		{
			return self::write($path, $content, self::OVERRIDE);
		}
		else
		{
			return self::write($path, $content);
		}
	}
}

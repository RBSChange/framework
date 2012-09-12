<?php
/**
 * @method ApacheService getInstance()
 */
class ApacheService extends change_BaseService
{
	/**
	 * @param string $module
	 * @param string $content
	 * @param integer $priority between 0 and 99
	 * @throws IOException
	 */
	public function generateSpecificConfFileForModule($module, $content, $priority = 50)
	{
		// Clear all existing conf file for this module (in case we changed the priority).
		$this->clearSpecificConfFilesForModule($module);
		
		// Generate the new conf file.
		$fileName = $this->getConfFileNameForModule($module, $priority);
		$filePath = $this->getGeneratedFilePath($fileName);
		$this->writeFileAndSetPermissions($filePath, $content);
	}
	
	/**
	 * @return string
	 */
	public function getGroup()
	{
		Framework::deprecated("no more implemented");
		return '';	
	}
	
	/**
	 * @return Void
	 */
	public function compileHtaccess()
	{
		$contents = array();
		
		// Get contents in modules.
		$modules = ModuleService::getInstance()->getPackageNames();
		foreach ($modules as $module)
		{
			$dir = $this->getApacheDirectoryByPackage($module);
			if (!is_null($dir))
			{
				$contents = array_merge($contents, $this->getContentsInDirectory($dir, $module));
			}
		}
		
		// Get contents in webapp.
		$dir = $this->getApacheDirectory();
		$contents = array_merge($contents, $this->getContentsInDirectory($dir, 'webapp'));

		// Sort them by priority (an alphabetical sort will work because the priority
		// always consists of two digits at the beginning of the name).
		ksort($contents);
		$content = '';
		// Write the file.
		if (count($contents) > 0)
		{
			foreach ($contents as $key => $value) 
			{
				$pathInfo = explode('/', $key);
				$content .= "##### Source File : " . str_replace('_', '/', $pathInfo[1])  .'/apache/' . $pathInfo[0]. "\n" . $this->applyReplacements($value) ."\n\n";
			}
		}
		else 
		{
			$content = '# No file to compile.';
		}
		$this->writeFileAndSetPermissions($this->getHtaccessPath(), $content);

		// Create the symlink.
		$this->createHtaccessSymlink();		
	}
	
	// Private methods.
	
	/**
	 * @param string $content
	 * @return string
	 */
	private function applyReplacements($content)
	{
		$from = array();
		$to = array();

		if (DIRECTORY_SEPARATOR === "\\")
		{
			$from[] = DIRECTORY_SEPARATOR;
			$to[] = '/';
		}
		$from[] = ' ';
		$to[] = "\\ ";
		$dr = str_replace($from, $to, DOCUMENT_ROOT);
		$wh = str_replace($from, $to, PROJECT_HOME);
		return str_replace(array('%{DOCUMENT_ROOT}', '%{PROJECT_HOME}'), 
				array($dr, $wh), $content);
	}
	
	/**
	 * Create the htaccess symlink in webapp/www.
	 */
	private function createHtaccessSymlink()
	{
		$linkPath = f_util_FileUtils::buildDocumentRootPath('.htaccess');
		f_util_FileUtils::mkdir(dirname($linkPath));
		$linkTarget = $this->getHtaccessPath();
		if (file_exists($linkPath))
		{
			// If there is already a file that is not a symlink: Exception.
			if (!is_link($linkPath))
			{
				Framework::warn("The file .htaccess already exists and is not a symlink. Replace it with a symlink to $linkTarget");
				f_util_FileUtils::unlink($linkPath);
				f_util_FileUtils::symlink($linkTarget, $linkPath);
			}
			// If there is already a symlink with a bad target, reset it.
			else if (readlink($linkPath) != $linkTarget)
			{
				f_util_FileUtils::unlink($linkPath);
				f_util_FileUtils::symlink($linkTarget, $linkPath);
			}
			// this is OK
		}
		// If there is no file just create the symlink.
		else
		{
			f_util_FileUtils::symlink($linkTarget, $linkPath);
		}
	}
	
	/**
	 * @param string $filePath
	 * @param string $content
	 */
	private function writeFileAndSetPermissions($filePath, $content)
	{
		if (file_exists($filePath))
		{
			f_util_FileUtils::write($filePath, $content, f_util_FileUtils::OVERRIDE);
		}
		else
		{
			f_util_FileUtils::writeAndCreateContainer($filePath, $content);
		}
	}
	
	/**
	 * @param string $module
	 * @param integer $priority between 0 and 99
	 * @return string
	 */
	private function getConfFileNameForModule($module, $priority)
	{
		// Convert the priority as a 2 character string.
		$priority = str_repeat('0', 2 - strlen(strval($priority))) . strval($priority);
		
		return $priority.'_'.$module.'.conf';
	}
	
	/**
	 * @param string $fileName
	 * @return string
	 */
	private function getGeneratedFilePath($fileName)
	{
		return $this->getApacheDirectory() . DIRECTORY_SEPARATOR . $fileName;
	}
	
	/**
	 * @return string
	 */
	private function getApacheDirectory()
	{
		return f_util_FileUtils::buildChangeBuildPath('apache');
	}
	
	/**
	 * @param string $package
	 * @return string
	 */
	private function getApacheDirectoryByPackage($package)
	{
		return change_FileResolver::getNewInstance()->getPath(str_replace('_', DIRECTORY_SEPARATOR, $package), 'apache');
	}
	
	/**
	 * @return string
	 */
	private function getHtaccessPath()
	{
		return f_util_FileUtils::buildChangeBuildPath('www.htaccess');
	}
	
	/**
	 * @param string $module
	 */
	private function clearSpecificConfFilesForModule($module)
	{
		$dir = $this->getApacheDirectory();
		if (!is_dir($dir))
		{
			return;
		}
		foreach (f_util_FileUtils::getDirFiles($dir) as $filePath)
		{
			$fileName = basename($filePath);
			if (preg_match('#^[0-9]{2}_'.$module.'\.conf$#i', $filePath) === 1)
			{
				f_util_FileUtils::unlink($filePath);
			}
		}
	}
	
	/**
	 * @param string $dir
	 * @return Array<String, String>
	 */
	private function getContentsInDirectory($dir, $package)
	{
		$contents = array();
		foreach (glob($dir."/*.conf") as $filePath)
		{
			$fileName = basename($filePath);
			$matches = null;
			if (preg_match('#^([0-9]{2})_([a-z]+)\.conf$#i', $fileName, $matches))
			{
				if (Framework::inDevelopmentMode())
				{
					$fileDevPath = dirname($filePath)."/".$matches[1]."_".$matches[2].".dev.conf";
					if (file_exists($fileDevPath))
					{
						$filePath = $fileDevPath;	
					}
				}
				$contents[$fileName.'/'.$package] = f_util_FileUtils::read($filePath);
			}
		}
		return $contents;
	}
}
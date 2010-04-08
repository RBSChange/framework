<?php
/**
 * framework_patch_0311
 * @package modules.framework
 */
class framework_patch_0311 extends patch_BasePatch
{
    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
	public function isCodePatch()
	{
		return true;
	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$check = f_util_FileUtils::buildWebeditPath('webapp', 'www', 'index.php');
		$oldWebApp = f_util_FileUtils::buildWebeditPath('.webapp');
		if (file_exists($check) && !is_link($check))
		{
			$this->log("Init project and webapp...");
			$webApp = f_util_FileUtils::buildWebeditPath('webapp');
			rename($webApp, $oldWebApp);
			f_util_System::exec('change.php compile-config');
			f_util_System::exec('change.php init-project');
			f_util_System::exec('change.php init-webapp');
		}
		
		if (is_dir($oldWebApp))
		{
			$this->log("Move old wepapp folders...");
			$from = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'apache');
			if (is_dir($from))
			{
				$this->log('Move : ' . $from);
				f_util_FileUtils::cp($from, f_util_FileUtils::buildChangeBuildPath('apache'), 
					f_util_FileUtils::OVERRIDE, array('.svn'));
				$to = f_util_FileUtils::buildAbsolutePath($oldWebApp, '.apache');
				rename($from, $to);
			}
			
			$from = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'seo');
			if (is_dir($from))
			{
				$this->log('Move : ' . $from);
				f_util_FileUtils::cp($from, f_util_FileUtils::buildChangeBuildPath('seo'), 
					f_util_FileUtils::OVERRIDE, array('.svn'));
				$to = f_util_FileUtils::buildAbsolutePath($oldWebApp, '.seo');
				rename($from, $to);
			}
			
			$from = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'modules');
			if (is_dir($from))
			{
				$this->log('Move : ' . $from);
				f_util_FileUtils::cp($from, f_util_FileUtils::buildOverridePath('modules'),
					f_util_FileUtils::OVERRIDE, array('.svn'));
				$to = f_util_FileUtils::buildAbsolutePath($oldWebApp, '.modules');
				rename($from, $to);
			}	
			
			$from = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'installedpatch');
			if (is_dir($from))
			{
				$this->log('Move : ' . $from);
				f_util_FileUtils::cp($from, f_util_FileUtils::buildWebeditPath('installedpatch'),
					f_util_FileUtils::OVERRIDE, array('.svn'));
				$to = f_util_FileUtils::buildAbsolutePath($oldWebApp, '.installedpatch');
				rename($from, $to);
			}	

			$from = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'media', 'backoffice');
			if (is_dir($from))
			{
				$this->log('Move : ' . $from);
				f_util_FileUtils::cp($from, f_util_FileUtils::buildWebeditPath('media', 'backoffice'),
					f_util_FileUtils::OVERRIDE, array('.svn'));
				$to = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'media', '.backoffice');
				rename($from, $to);
			}
			
			$from = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'media', 'frontoffice');
			if (is_dir($from))
			{
				$this->log('Move : ' . $from);
				f_util_FileUtils::cp($from, f_util_FileUtils::buildWebeditPath('media', 'frontoffice'),
					f_util_FileUtils::OVERRIDE, array('.svn'));
				$to = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'media', '.frontoffice');
				rename($from, $to);
			}
			
			$from = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'www', 'icons');
			if (is_dir($from))
			{
				$this->log('Move : ' . $from);
				f_util_FileUtils::cp($from, f_util_FileUtils::buildWebeditPath('changeicons'),
					f_util_FileUtils::OVERRIDE, array('.svn'));
				$to = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'www', '.icons');
				rename($from, $to);
			}
			
			$from = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'hostspecificresources');
			if (is_dir($from))
			{
				$this->log('Move : ' . $from);
				f_util_FileUtils::cp($from, f_util_FileUtils::buildOverridePath('hostspecificresources'),
					f_util_FileUtils::OVERRIDE, array('.svn'));
				$to = f_util_FileUtils::buildAbsolutePath($oldWebApp, '.hostspecificresources');
				rename($from, $to);
			}
			
			$from = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'patch');
			if (is_dir($from))
			{
				$this->log('Move : ' . $from);
				f_util_FileUtils::cp($from, f_util_FileUtils::buildOverridePath('webapp', 'patch'),
					f_util_FileUtils::OVERRIDE, array('.svn'));
				$to = f_util_FileUtils::buildAbsolutePath($oldWebApp, '.patch');
				rename($from, $to);
			}
			
			$from = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'framework' ,'config', 'browscap.ini');
			if (file_exists($from))
			{
				$this->log('Move : ' . $from);
				f_util_FileUtils::cp($from, f_util_FileUtils::buildWebeditPath('config', 'browscap.ini'),
					f_util_FileUtils::OVERRIDE);
				$to = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'www', '.icons');
				rename($from, $to);
			}			
		}
		
		$this->log("Add Symlink for bin directory compatibility");
		$from = f_util_FileUtils::buildAbsolutePath($oldWebApp, 'bin');
		if (file_exists($from))
		{
			$this->log('Add Symlink for bin directory compatibility : ' . $from);
			
			$tagret = f_util_FileUtils::buildWebeditPath('bin');
			$link = f_util_FileUtils::buildWebeditPath('webapp', 'bin');
			f_util_FileUtils::symlink($tagret, $link, f_util_FileUtils::OVERRIDE);

			$this->logWarning('PLEASE UPDATE CRONTAB : ' . $link . ' -> ' . $tagret);
			$to = f_util_FileUtils::buildAbsolutePath($oldWebApp, '.bin');
			rename($from, $to);
		}			
		$this->log("Replace  {HttpHost}/icons/ by {IconsBase}/ and url(/icons/ by url(/changeicons/");	
		$this->log("In specifique modules ...");
		$dir = f_util_FileUtils::buildWebeditPath('modules');
		$this->updateChangeIcons($dir);
		
		$this->log("In override modules ...");
		$dir = f_util_FileUtils::buildOverridePath('modules');
		$this->updateChangeIcons($dir);
		
		$this->log("compile-url-rewriting ... urlrewriting_rules.php");
		f_util_System::exec('change.php compile-url-rewriting');

		$this->log("compile-htaccess ... .htaccess");
		f_util_System::exec('change.php compile-htaccess');

	}

	private function updateChangeIcons($dir)
	{
		$dh = opendir($dir);
		
		while (($file = readdir($dh)) !== false)
		{
			if ($file[0] === '.') {continue;}
			$filePath = $dir . DIRECTORY_SEPARATOR . $file;
			
			if (is_link($filePath)) {continue;}
			
			if (is_dir($filePath))
			{
				$this->updateChangeIcons($filePath);
			}
			else if (is_writable($filePath))
			{
				$fileExtension = strtolower(substr($file, strrpos($file, '.') + 1));
				if ($fileExtension === 'xml')
				{
					$content = file_get_contents($filePath);
					if (strpos($content, '{HttpHost}/icons/') !== false)
					{
						echo 'Update : ' . $filePath . "\n";
						$content = str_replace('{HttpHost}/icons/', '{IconsBase}/', $content);
						file_put_contents($filePath, $content);
					}
				}
				else if ($fileExtension === 'css')
				{
					$content = file_get_contents($filePath);
					if (strpos($content, 'url(/icons/') !== false)
					{
						echo 'Update : ' . $filePath . "\n";
						$content = str_replace('url(/icons/', 'url(/changeicons/', $content);
						file_put_contents($filePath, $content);						
					}
				}
			}
		}	
		closedir($dh);
	}
	
	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'framework';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0311';
	}
}
<?php
class commands_InitWebapp extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}

	function getAlias()
	{
		return "iw";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "init webapp folder";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Init webapp ==");

		$this->getParent()->executeCommand("compileConfig");

		// Copy files
		$this->loadFramework();
		
		$rootSymLink = (WEBEDIT_HOME != DOCUMENT_ROOT);
		if ($rootSymLink)
		{
			f_util_FileUtils::mkdir(DOCUMENT_ROOT);
		}
				
		$exclude = array(".svn");
		$home = f_util_FileUtils::buildWebeditPath();

		$this->message("Import framework home files");
		$frameworkWebapp = f_util_FileUtils::buildWebeditPath("framework", "builder", "home");
		f_util_FileUtils::cp($frameworkWebapp, $home, f_util_FileUtils::OVERRIDE | f_util_FileUtils::APPEND, $exclude);
		$exclude[] = "www";
		//Add .htaccess for hide system folder
		$this->message("Add missing .htaccess");
		$htAccess = f_util_FileUtils::buildWebeditPath("framework", "builder", "home", "bin", ".htaccess");
		$to = f_util_FileUtils::buildCachePath('.htaccess');
		if (!file_exists($to)) 
		{
			f_util_FileUtils::cp($htAccess, $to);
		}
		
		

		foreach (array('config', 'securemedia', 'build', 'log', 'libs', 'modules', 'override', 'mailbox', 'cache/autoload', 'cache/' . PROFILE) as $hiddeDir) 
		{
			$to = f_util_FileUtils::buildWebeditPath($hiddeDir, '.htaccess');
			if (is_dir(dirname($to)))
			{
				if (!file_exists($to)) 
				{
					try 
					{
						f_util_FileUtils::cp($htAccess, $to);
					} 
					catch (Exception $e)
					{
						$this->warnMessage($e->getMessage());
					}
				}
			}
		}
		
		$this->message("Create /publicmedia folder");
		f_util_FileUtils::symlink(f_util_FileUtils::buildWebeditPath("media"), f_util_FileUtils::buildDocumentRootPath("publicmedia"), f_util_FileUtils::OVERRIDE);
		
		// Icons symlink
		if (file_exists(WEBEDIT_HOME."/libs/icons"))
		{
			$this->message("Create icons symlink");
			$iconsLink = f_util_FileUtils::buildWebeditPath("media", "changeicons");
			f_util_FileUtils::symlink(WEBEDIT_HOME."/libs/icons", $iconsLink, f_util_FileUtils::OVERRIDE);			
		}
		elseif (($computedDeps = $this->getComputedDeps()) && isset($computedDeps["lib"]["icons"]))
		{
			$this->warnMessage(WEBEDIT_HOME."/libs/icons does not exists. Did you ran init-project ?");
		}
		
		foreach (glob(WEBEDIT_HOME . '/modules/*/webapp') as $moduleWebapp)
		{
			$this->message("Import ".$moduleWebapp." files");
			f_util_FileUtils::cp($moduleWebapp, $home, f_util_FileUtils::OVERRIDE | f_util_FileUtils::APPEND, $exclude);
			$deprecatedWWW = f_util_FileUtils::buildPath($moduleWebapp, 'www');
			if (is_dir($deprecatedWWW))
			{
				f_util_FileUtils::cp($deprecatedWWW, $home, f_util_FileUtils::OVERRIDE | f_util_FileUtils::APPEND, $exclude);
			}
		}
		
		foreach (glob(PROJECT_OVERRIDE . '/modules/*/webapp') as $moduleWebapp)
		{
			$this->message("Import ".$moduleWebapp." files");
			f_util_FileUtils::cp($moduleWebapp, $home, f_util_FileUtils::OVERRIDE | f_util_FileUtils::APPEND, $exclude);
			$deprecatedWWW = f_util_FileUtils::buildPath($moduleWebapp, 'www');
			if (is_dir($deprecatedWWW))
			{
				f_util_FileUtils::cp($deprecatedWWW, $home, f_util_FileUtils::OVERRIDE | f_util_FileUtils::APPEND, $exclude);
			}
		}

		if ($rootSymLink)
		{
			$this->addRootLink(WEBEDIT_HOME);
		}
		
		// Apply file policy
		$this->getParent()->executeCommand("applyWebappPolicy");
		
		$this->quitOk("Webapp initialized");
	}
	
	private function addRootLink($targetDir)
	{
		$targetDir .= DIRECTORY_SEPARATOR;
		$exclude = array('apache', 'bin', 'log', 'build', 'config', 'framework', 'libs', 'modules', 'securemedia', 
			'webapp', 'mailbox', 'override', 'profile', 'change.xml', 'change.properties',
			'migration', 'mockup', 'installedpatch', 'pear', 'themes', 'target');
		$dh = opendir($targetDir);
		while (($file = readdir($dh)) !== false)
		{
			if (strpos($file, '.') === 0) {continue;}
			if (in_array($file, $exclude)) {continue;}
			
			$target = $targetDir.$file;
			if (is_file($target) && substr($target, -4) !=  '.php') {continue;}
			
			$link = f_util_FileUtils::buildDocumentRootPath($file);
			if (strpos($link, $target) !== 0)
			{
				f_util_FileUtils::symlink($target, $link, f_util_FileUtils::OVERRIDE);
			}
		}
		closedir($dh);
	}
}
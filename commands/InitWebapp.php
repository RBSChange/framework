<?php
class commands_InitWebapp extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	public function getUsage()
	{
		return "";
	}

	public function getAlias()
	{
		return "iw";
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return "init webapp folder";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		$this->message("== Init webapp ==");

		$this->executeCommand("compile-config");

		// Copy files
		$this->loadFramework();
		
		$rootSymLink = (f_util_FileUtils::buildProjectPath() != f_util_FileUtils::buildDocumentRootPath());
		if ($rootSymLink)
		{
			f_util_FileUtils::mkdir(f_util_FileUtils::buildDocumentRootPath());
		}
				
		$home = f_util_FileUtils::buildProjectPath();

		$this->message("Import framework home files");
		$frameworkWebapp = f_util_FileUtils::buildFrameworkPath("builder", "home");
		f_util_FileUtils::cp($frameworkWebapp, $home, f_util_FileUtils::OVERRIDE | f_util_FileUtils::APPEND);

		
		$exclude =  array(".svn", ".git", "www");
		//Add .htaccess for hide system folder
		$this->message("Add missing .htaccess");
		$htAccess = f_util_FileUtils::buildFrameworkPath("builder", "home", "bin", ".htaccess");
		foreach (array('config', 'securemedia', 'repository', 'build', 'log', 'libs', 'modules', 'themes', 'override', 'cache/autoload', 'cache/project') as $hiddeDir) 
		{
			$to = f_util_FileUtils::buildProjectPath($hiddeDir, '.htaccess');
			if (is_dir(dirname($to)))
			{
				if (!file_exists($to)) 
				{
					try 
					{
						f_util_FileUtils::cp($htAccess, $to);
						$this->message('Add: ' . $to);
					} 
					catch (Exception $e)
					{
						$this->warnMessage($e->getMessage());
					}
				}
			}
		}
		
		$this->message("Create /publicmedia folder");
		f_util_FileUtils::symlink(f_util_FileUtils::buildProjectPath("media"), f_util_FileUtils::buildDocumentRootPath("publicmedia"), f_util_FileUtils::OVERRIDE);
		
		// Icons symlink
		if (file_exists(PROJECT_HOME."/libs/icons"))
		{
			$this->message("Create icons symlink");
			$iconsLink = f_util_FileUtils::buildProjectPath("media", "changeicons");
			f_util_FileUtils::symlink(PROJECT_HOME."/libs/icons", $iconsLink, f_util_FileUtils::OVERRIDE);			
		}
		elseif (($computedDeps = $this->getComputedDeps()) && isset($computedDeps["lib"]["icons"]))
		{
			$this->warnMessage(PROJECT_HOME."/libs/icons does not exists. Did you ran init-project ?");
		}
		
		foreach (glob(PROJECT_HOME . '/modules/*/webapp') as $moduleWebapp)
		{
			$this->message("Import ".$moduleWebapp." files");
			f_util_FileUtils::cp($moduleWebapp, $home, f_util_FileUtils::OVERRIDE | f_util_FileUtils::APPEND, $exclude);
			$deprecatedWWW = f_util_FileUtils::buildPath($moduleWebapp, 'www');
			if (is_dir($deprecatedWWW))
			{
				f_util_FileUtils::cp($deprecatedWWW, $home, f_util_FileUtils::OVERRIDE | f_util_FileUtils::APPEND, $exclude);
			}
		}
		
		foreach (glob(PROJECT_HOME . '/override/modules/*/webapp') as $moduleWebapp)
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
			$this->addRootLink(PROJECT_HOME);
		}
		
		// Apply file policy
		$this->executeCommand("apply-webapp-policy");
		
		$this->quitOk("Webapp initialized");
	}
	
	private function addRootLink($targetDir)
	{
		$targetDir .= DIRECTORY_SEPARATOR;
		$defautLinks = "cache,changeicons,fckeditor,fckeditorbrowser,media,publicmedia,migration,install,".
			"index.php,xchrome_controller.php,xul_controller.php,site-disabled.php,sessionKeepAlive.php,".
			"listener.php,changecron.php,changescriptexec.php";
		
		$links = explode(',', Framework::getConfigurationValue('general/linkToDocumentRoot', $defautLinks));
		$links = array_unique(array_map('trim', $links));		
		
		$dh = opendir($targetDir);
		while (($file = readdir($dh)) !== false)
		{
			if (!in_array($file, $links)) 
			{
				continue;
			}
			
			$target = $targetDir.$file;
			$link = f_util_FileUtils::buildDocumentRootPath($file);
			if (strpos($link, $target) !== 0)
			{
				$this->message("Add symlink for ".$file);
				f_util_FileUtils::symlink($target, $link, f_util_FileUtils::OVERRIDE);
			}
		}
		closedir($dh);
	}
}
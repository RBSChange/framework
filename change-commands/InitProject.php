<?php
class commands_InitProject extends commands_AbstractChangeCommand
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
		return "ip";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "init the project layout";
	}
	
	function generateDefaultConfig()
	{
		
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Initializing project ==");

		$computedDeps = $this->getComputedDeps();
		
		$newToAutoload = array();
		if ($this->createFrameworkLink())
		{
			$newToAutoload[] = "framework";
		}

		// config directory: generate default config files
		$builderResourcePath = f_util_FileUtils::buildWebeditPath("framework", "builder");
		f_util_FileUtils::mkdir("config");
		// base config
		$fileName = 'config/project.xml';
		if (!file_exists(f_util_FileUtils::buildWebeditPath($fileName)))
		{
			$this->message("Create $fileName");
			$baseProject = f_util_FileUtils::read("$builderResourcePath/config/project.xml");
			$baseSubstitutions = array("project" => $this->getProjectName(),
				"author" => $this->getAuthor(), "serverHost" => $this->getServerHost(),
				"serverFqdn" => $this->getServerFqdn());
			f_util_FileUtils::write($fileName, $this->substitueVars($baseProject, $baseSubstitutions), f_util_FileUtils::OVERRIDE);
		}
		else
		{
			$this->warnMessage($fileName.' already exists.');
		}

		// current user config
		$fileName = 'config/project.'.$this->getProfile().'.xml';
		if (!file_exists(f_util_FileUtils::buildWebeditPath($fileName)))
		{
			$this->message("Create $fileName");
			$profilProject = f_util_FileUtils::read("$builderResourcePath/config/project_profil.xml");
			$profilSubstitutions = array("project" => $this->getProjectName(),
				"author" => $this->getAuthor(), "serverHost" => $this->getServerHost(),
				"database" => str_replace(".", "_", "C4_".$this->getAuthor()."_".$this->getProjectName()),
				"database_host" => $this->getDatabaseHost(),
				"serverFqdn" => $this->getServerFqdn(), "fakeMailDef" => $this->getFakeMailDef(),
				"solrDef" => $this->getSolrDef());
			f_util_FileUtils::write($fileName, $this->substitueVars($profilProject, $profilSubstitutions), f_util_FileUtils::OVERRIDE);
		}
		else
		{
			$this->warnMessage($fileName.' already exists.');
		}

		// libs directory
		f_util_FileUtils::mkdir("libs");

		foreach ($computedDeps["lib"] as $libName => $libInfo)
		{
			$this->message("Symlink lib/$libName-".$libInfo["version"]);
			if (f_util_FileUtils::symlink($libInfo["path"], "libs/".$libName, f_util_FileUtils::OVERRIDE))
			{
				$newToAutoload[] = "libs/".$libName;
			}
		}
				
		if (isset($computedDeps["PEAR_DIR"]) && isset($computedDeps["lib-pear"]))
		{
			$pearDir = $computedDeps["PEAR_DIR"]; 
			f_util_FileUtils::mkdir("libs/pearlibs");
			foreach ($computedDeps["lib-pear"] as $libName => $libInfo)
			{
				$this->message("Symlink pearlibs/$libName-".$libInfo["version"]);
				if (f_util_FileUtils::symlink($libInfo["path"], "libs/pearlibs/".$libName, f_util_FileUtils::OVERRIDE))
				{
					if ($computedDeps['PEAR_WRITEABLE'])
					{
						$this->message("copy libs/pearlibs/".$libName . " to " . $pearDir);
						f_util_FileUtils::cp("libs/pearlibs/".$libName, $pearDir, 
							f_util_FileUtils::OVERRIDE + f_util_FileUtils::APPEND, array('change.xml', 'tests', 'docs'));
					}
					else
					{
						$this->message("Please check if $libName-".$libInfo["version"] . " PEAR extension is correctly installed!");
					}
				}
			}
			$newToAutoload[] = $pearDir;
		}
		else
		{
			$newToAutoload[] = $computedDeps["PEAR_DIR"];
		}
		
		foreach ($computedDeps["change-lib"] as $libName => $libInfo)
		{
			if ($libName == "framework")
			{
				continue;
			}
			$this->message("Symlink change-lib/$libName-".$libInfo["version"]);
			if (f_util_FileUtils::symlink($libInfo["path"], "libs/".$libName, f_util_FileUtils::OVERRIDE))
			{
				$newToAutoload[] = "libs/".$libName;
			}
		}
				
		// cache directory
		f_util_FileUtils::mkdir("cache/".$this->getProfile());

		// build directory
		f_util_FileUtils::mkdir("build/".$this->getProfile());

		// log directory
		f_util_FileUtils::mkdir("log/".$this->getProfile());

		f_util_FileUtils::mkdir("mailbox");
		f_util_FileUtils::mkdir("modules");

		$this->getParent()->executeCommand("compileConfig");
		$this->loadFramework();
		$classResolver = ClassResolver::getInstance();
		foreach ($newToAutoload as $dir)
		{
			$this->message("Add $dir to autoload");
			$classResolver->appendDir(realpath($dir));
		}

		// init-file-policy
		$this->getParent()->executeCommand("applyProjectPolicy");

		$this->quitOk("Project initialized");
	}

	private function getProjectName()
	{
		return basename(realpath("."));
	}

	private function getServerHost()
	{
		foreach (array(getenv("HOME")."/.change/host", "/etc/change/host") as $file)
		{
			if (file_exists($file))
			{
				return f_util_FileUtils::read($file);
			}
		}
		return null;
	}

	private function getServerFqdn()
	{
		$serverHost = $this->getServerHost();
		if ($serverHost !== null)
		{
			return $this->getProjectName().".".$this->getAuthor().".".$serverHost;
		}
		$projectName = $this->getProjectName();
		if (f_util_StringUtils::contains($projectName, "."))
		{
			return $projectName;
		}
		return $projectName.".".$this->getAuthor()."."."localhost";
	}
	
	private function getFakeMailDef()
	{
		$props = $this->getProperties();
		if ($props->hasProperty("FAKE_EMAIL"))
		{
			return "<!-- Comment the following to disable 'fake email' functionality -->
		<define name=\"FAKE_EMAIL\">".$props->getProperty("FAKE_EMAIL")."</define>";
		}
		return '<!-- Uncomment and fill the following to enable FAKE_EMAIL functionality
		<define name="FAKE_EMAIL">xxxx.xxxx@rbs.fr</define>
		-->';
	}
	
	private function getSolrDef()
	{
		$props = $this->getProperties();
		if ($props->hasProperty("SOLR_SHARED"))
		{
			return '<define name="SOLR_INDEXER_URL">'.$props->getProperty("SOLR_SHARED").'</define>
  		<define name="SOLR_INDEXER_CLIENT">'.$this->getAuthor().'.'.$this->getProjectName().'</define>';
		}
		
		return '<!-- Uncomment and fill the following to enable indexation. Then execute "indexer reset" command. 
  		<define name="SOLR_INDEXER_URL">http://127.0.0.1:8080/solr_shared</define>
  		<define name="SOLR_INDEXER_CLIENT">'.$this->getAuthor().'.'.$this->getProjectName().'</define>		
		-->';
	}
	
	private function getDatabaseHost()
	{
		return $this->getProperties()->getProperty("DATABASE_HOST", "localhost");
	}

	private function substitueVars($content, $substitutions)
	{
		$from = array();
		foreach ($substitutions as $key => $value)
		{
			$from[] = '${'.$key.'}';
		}
		return str_replace($from, array_values($substitutions), $content);
	}
}

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
		$parent = $this->getParent();
		$parent->executeCommand("updateDependencies");
		
		$computedDeps = $this->getComputedDeps();
		
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

		// build directory
		f_util_FileUtils::mkdir("build/".$this->getProfile());

		// log directory
		f_util_FileUtils::mkdir("log/".$this->getProfile());

		f_util_FileUtils::mkdir("mailbox");
		
		f_util_FileUtils::mkdir("themes");
		
		$this->getParent()->executeCommand("compileConfig");
		$this->loadFramework();

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
		foreach (array_keys($substitutions) as $key)
		{
			$from[] = '${'.$key.'}';
		}
		return str_replace($from, array_values($substitutions), $content);
	}
}

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

		$this->executeCommand("updateDependencies");
				
		// config directory: generate default config files
		$builderResourcePath = f_util_FileUtils::buildFrameworkPath("builder");
		f_util_FileUtils::mkdir("config");
		// base config
		$fileName = 'config/project.xml';
		if (!file_exists(f_util_FileUtils::buildProjectPath($fileName)))
		{
			$this->message("Create $fileName");
			$baseProject = f_util_FileUtils::read("$builderResourcePath/config/project.xml");
			$baseSubstitutions = array("project" => $this->getProjectName(), "author" => $this->getAuthor(),);
			f_util_FileUtils::write($fileName, $this->substitueVars($baseProject, $baseSubstitutions), f_util_FileUtils::OVERRIDE);
		}
		else
		{
			$this->warnMessage($fileName.' already exists.');
		}

		// current user config
		$fileName = 'config/project.'.$this->getProfile().'.xml';
		if (!file_exists(f_util_FileUtils::buildProjectPath($fileName)))
		{
			$this->message("Create $fileName");
			$profilProject = f_util_FileUtils::read("$builderResourcePath/config/project_profil.xml");
			$profilSubstitutions = array("project" => $this->getProjectName(), "author" => $this->getAuthor(), 
				"serverHost" => $this->getServerHost(),
				"database" => str_replace(".", "_", "C4_".$this->getAuthor()."_".$this->getProjectName()),
				"database_host" => $this->getDatabaseHost(),
				"serverFqdn" => $this->getServerFqdn(),
				"solrDef" => $this->getSolrDef());
			f_util_FileUtils::write($fileName, $this->substitueVars($profilProject, $profilSubstitutions), f_util_FileUtils::OVERRIDE);
		}
		else
		{
			$this->warnMessage($fileName.' already exists.');
		}

		// build directory
		f_util_FileUtils::mkdir("build/project");

		// log directory
		f_util_FileUtils::mkdir("log/project");
		
		// cache directory
		f_util_FileUtils::mkdir("cache/project");

		f_util_FileUtils::mkdir("mailbox");
		
		$this->executeCommand("compileConfig");
		$this->loadFramework();

		// init-file-policy
		$this->executeCommand("applyProjectPolicy");

		$this->quitOk("Project initialized");
	}

	private function getProjectName()
	{
		return basename(realpath("."));
	}

	private function getServerHost()
	{
		if (isset($_SERVER['SERVER_NAME']))
		{
			return $_SERVER['SERVER_NAME'];
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
	
	
	private function getSolrDef()
	{
		$props = $this->getProperties();
		if ($props->hasProperty("SOLR_SHARED"))
		{
			$url = $props->getProperty("SOLR_SHARED");
		}
		else
		{
			$url = 'http://127.0.0.1:8983/solr';
		}
		$solr = '<solr>
			<entry name="clientId">'.$this->getAuthor().'.'.$this->getProjectName().'</entry>
			<entry name="url">'.$url.'</entry>
		</solr>';
		
		if ($props->hasProperty("SOLR_SHARED"))
		{
			return $solr;
		}
		else
		{ 
			return '<!-- '. $solr . ' -->';
		}
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

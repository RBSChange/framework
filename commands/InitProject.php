<?php
class commands_InitProject extends c_ChangescriptCommand
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
		return "ip";
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return "init the project layout";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		if (!file_exists(f_util_FileUtils::buildProjectPath('config/project.xml'))
			|| !file_exists(f_util_FileUtils::buildProjectPath('change.properties')))
		{
			$this->quitError('You have no project configuration. Please create your config/project.xml and change.properties files');
		}
		
		$this->message("== Initializing project ==");

		$this->executeCommand("update-dependencies");
	
		// build directory
		f_util_FileUtils::mkdir("build/project");

		// log directory
		f_util_FileUtils::mkdir("log/project");
		
		// cache directory
		f_util_FileUtils::mkdir("cache/project");

		f_util_FileUtils::mkdir("themes");
		
		$this->executeCommand("compile-config");
		
		$this->executeCommand("compile-autoload");
		$this->loadFramework();

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
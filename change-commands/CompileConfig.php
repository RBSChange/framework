<?php
class commands_CompileConfig extends c_ChangescriptCommand
{
	/**
	 * @return String
	 */
	public function getUsage()
	{
		return "";
	}
	
	public function getAlias()
	{
		return "cconf";
	}
	
	/**
	 * @return String
	 */
	public function getDescription()
	{
		return "compile the change configuration";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		$this->message("== Compile config ==");
		$this->getBootStrap()->cleanDependenciesCache();
		$cd = $this->getComputedDeps();
		
		$projectParser = new config_ProjectParser();
		
		$oldAndCurrent = $projectParser->execute($cd);
		
		if (class_exists('Framework', false))
		{
			// Framework is loaded and configuration may have changed
			Framework::reloadConfiguration();
		}
		
		//OAuth identification files
		if (!is_dir(PROJECT_HOME . '/build/config/oauth/script'))
		{
			mkdir(PROJECT_HOME . '/build/config/oauth/script', 0777, true);
		}
		
		if (Framework::hasConfiguration('oauth/consumer'))
		{
			$consumer = Framework::getConfiguration('oauth/consumer');
			file_put_contents(PROJECT_HOME . '/build/config/oauth/script/consumer.txt', $consumer);
		}
		else if (!file_exists(PROJECT_HOME . '/build/config/oauth/script/consumer.txt'))
		{
			$profile = trim(file_get_contents(PROJECT_HOME . '/profile'));
			$consumer = $profile .'#' . $profile;
			file_put_contents(PROJECT_HOME . '/build/config/oauth/script/consumer.txt', $consumer);
		}
		
		if (Framework::hasConfiguration('oauth/token'))
		{
			$token = Framework::getConfiguration('oauth/token');
			file_put_contents(PROJECT_HOME . '/build/config/oauth/script/token.txt', $token);
		}
		else if (!file_exists(PROJECT_HOME . '/build/config/oauth/script/token.txt'))
		{	
			$ts = time();
			$token = md5($ts . mt_rand()) .'#' . md5($ts . mt_rand());
			file_put_contents(PROJECT_HOME . '/build/config/oauth/script/token.txt', $token);
		}
		
		if ($oldAndCurrent !== null)
		{
			$old = $oldAndCurrent["old"];
			$current = $oldAndCurrent["current"];
			
			if ($old["defines"]["LOGGING_LEVEL"] != $current["defines"]["LOGGING_LEVEL"])
			{
				$this->message("LOGGING_LEVEL is now ".$current["defines"]["LOGGING_LEVEL"]);
				if (!isset($options['ignoreListener']))
				{
					$this->addListeners('after', "compile-js-dependencies");
				}
			}
			if ($old["defines"]["SUPPORTED_LANGUAGES"] != $current["defines"]["SUPPORTED_LANGUAGES"])
			{
				$this->message("SUPPORTED_LANGUAGES changed");
				if (!isset($options['ignoreListener']))
				{
					$this->addListeners('after', "generate-database");
					$this->addListeners('after', "compile-editors-config"); 	
				}
			}
			if ($old["defines"]["CHANGE_USE_CORRECTION"] != $current["defines"]["CHANGE_USE_CORRECTION"]
			 || $old["defines"]["CHANGE_USE_WORKFLOW"] != $current["defines"]["CHANGE_USE_WORKFLOW"])
			{
				$this->message("CHANGE_USE_CORRECTION or CHANGE_USE_WORKFLOW changed");
				if (!isset($options['ignoreListener']))
				{
					$this->addListeners('after', "compile-documents");	
				}
			}
			
			if ($old["defines"]["DEVELOPMENT_MODE"] != $current["defines"]["DEVELOPMENT_MODE"])
			{
				$this->message("DEVELOPMENT_MODE is now ".$current["defines"]["DEVELOPMENT_MODE"]);
				
				if (!isset($options['ignoreListener']))
				{
					$this->addListeners('after', "clear-webapp-cache");
				}	
			}
		}
		
		$this->loadFramework();
		
		$this->quitOk("Config compiled");
	}
}
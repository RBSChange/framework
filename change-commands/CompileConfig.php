<?php
class commands_CompileConfig extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "[options]
Where options in:
  --no-auto-changes: do not launch other commands on configuration changes";
	}
	
	function getAlias()
	{
		return "cconf";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "compile the change configuration";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile config ==");
		
		$projectParser = new config_ProjectParser();
		$oldAndCurrent = $projectParser->execute($this->getComputedDeps());
		$this->getParent()->setEnvVar("commands_CompileConfig_oldAndCurrent", $oldAndCurrent);
		
		if (defined("FRAMEWORK_HOME"))
		{
			// Framework is loaded and configuration may have changed
			Framework::reloadConfiguration(PROFILE);
		}
		
		//OAuth identification files
		if (!is_dir(WEBEDIT_HOME . '/build/config/oauth/script'))
		{
			mkdir(WEBEDIT_HOME . '/build/config/oauth/script', 0777, true);
		}
		
		if (Framework::hasConfiguration('oauth/consumer'))
		{
			$consumer = Framework::getConfiguration('oauth/consumer');
			file_put_contents(WEBEDIT_HOME . '/build/config/oauth/script/consumer.txt', $consumer);
		}
		else if (!file_exists(WEBEDIT_HOME . '/build/config/oauth/script/consumer.txt'))
		{
			$profile = trim(file_get_contents(WEBEDIT_HOME . '/profile'));
			$consumer = $profile .'#' . $profile;
			file_put_contents(WEBEDIT_HOME . '/build/config/oauth/script/consumer.txt', $consumer);
		}
		
		if (Framework::hasConfiguration('oauth/token'))
		{
			$token = Framework::getConfiguration('oauth/token');
			file_put_contents(WEBEDIT_HOME . '/build/config/oauth/script/token.txt', $token);
		}
		else if (!file_exists(WEBEDIT_HOME . '/build/config/oauth/script/token.txt'))
		{	
			$ts = time();
			$token = md5($ts . mt_rand()) .'#' . md5($ts . mt_rand());
			file_put_contents(WEBEDIT_HOME . '/build/config/oauth/script/token.txt', $token);
		}
		
		
		
		if ($oldAndCurrent !== null)
		{
			$old = $oldAndCurrent["old"];
			$current = $oldAndCurrent["current"];
			
			if ($old["defines"]["AG_LOGGING_LEVEL"] != $current["defines"]["AG_LOGGING_LEVEL"])
			{
				$this->message("AG_LOGGING_LEVEL is now ".$current["defines"]["AG_LOGGING_LEVEL"]);
				if (isset($options["no-auto-changes"]))
				{
					$this->warnMessage("You must run manually compile-js-dependencies");
				}
				else
				{
					$this->getParent()->executeCommand("compile-js-dependencies");
				}
			}
			if ($old["defines"]["AG_SUPPORTED_LANGUAGES"] != $current["defines"]["AG_SUPPORTED_LANGUAGES"])
			{
				$this->message("AG_SUPPORTED_LANGUAGES changed");
				if (isset($options["no-auto-changes"]))
				{
					$this->warnMessage("You must run manually generate-database");
				}
				else
				{
					$parent = $this->getParent();
					$parent->executeCommand("generate-database");
					$parent->executeCommand("compile-editors-config"); 	
				}
			}
			if ($old["defines"]["CHANGE_USE_CORRECTION"] != $current["defines"]["CHANGE_USE_CORRECTION"]
			 || $old["defines"]["CHANGE_USE_WORKFLOW"] != $current["defines"]["CHANGE_USE_WORKFLOW"])
			{
				$this->message("CHANGE_USE_CORRECTION or CHANGE_USE_WORKFLOW changed");
				if (isset($options["no-auto-changes"]))
				{
					$this->warnMessage("You must run manually compile-documents");
				}
				else
				{
					$this->getParent()->executeCommand("compile-documents");	
				}
				
			}
			if ($old["defines"]["AG_DEVELOPMENT_MODE"] != $current["defines"]["AG_DEVELOPMENT_MODE"])
			{
				$this->message("AG_DEVELOPMENT_MODE is now ".$current["defines"]["AG_DEVELOPMENT_MODE"]);
				
				if (isset($options["no-auto-changes"]))
				{
					$this->warnMessage("You must run manually manage:
- webapp cache");
				}
				else
				{
					$this->loadFramework();
					
					CacheService::getInstance()->clearAllWebappCache();
					$this->okMessage("webapp cache cleared");
				}	
			}
		}
		
		$this->quitOk("Config compiled");
	}
}

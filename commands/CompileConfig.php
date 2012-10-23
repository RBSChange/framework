<?php
class commands_CompileConfig extends c_ChangescriptCommand
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
		return "cconf";
	}
	
	/**
	 * @return string
	 */
	public function getDescription()
	{
		return "compile the change configuration";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		require_once PROJECT_HOME . '/Change/Application.php';
		\Change\Application::getInstance()->start();
		$this->message("== Compile config ==");
		
		$this->getBootStrap()->cleanDependenciesCache();

		$cs = change_ConfigurationService::getInstance();
		$oldAndCurrent = $cs->compile($this->getComputedDeps());

		// Framework is loaded and configuration may have changed
		$cs->loadConfiguration();

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

		
		$this->loadFramework();

		$this->quitOk("Config compiled");
	}
}
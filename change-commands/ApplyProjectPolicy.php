<?php
class commands_ApplyProjectPolicy extends commands_AbstractChangeCommand
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
		return "app";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Apply project policy: ownership & permissions";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Apply project policy ==");
		
		$user = $this->getAuthor();
		$apacheGroup = $this->getApacheGroup();
		
		$readWriteDirs = array("cache", "build", "log", "media", "securemedia", "mailbox", "modules"); 
		foreach ($readWriteDirs as $dir)
		{
			$this->message("Apply '$dir' dir policy");
			f_util_FileUtils::chown($dir, $user, $apacheGroup, true);
			f_util_FileUtils::chmod($dir, "2775", true);
		}
		
		$this->quitOk("Project policy files applied");
	}
}
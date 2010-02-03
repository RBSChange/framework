<?php
class commands_ApplyWebappPolicy extends commands_AbstractChangeCommand
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
		return "awp";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Apply webapp policy: ownership & permissions";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Apply webapp policy ==");
		
		$apacheGroup = $this->getApacheGroup();
		$user = $this->getUser();
		
		foreach (array("webapp/www/cache", "webapp/modules", "webapp/apache") as $dir)
		{
			$this->message("Apply '$dir' dir policy");
			f_util_FileUtils::chown($dir, $user, $apacheGroup, true);
			f_util_FileUtils::chmod($dir, "2775", true);
		}
		f_util_FileUtils::chmod("webapp/bin", "775", true);
		
		$this->quitOk("Webapp files policy applied");
	}
}
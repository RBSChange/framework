<?php
class commands_ApplyWebappPolicy extends c_ChangescriptCommand
{
	/**
	 * @return string
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
	 * @return string
	 */
	function getDescription()
	{
		return "Apply webapp policy: ownership & permissions";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Apply webapp policy ==");
		
		$this->loadFramework();
		$apacheGroup = $this->getApacheGroup();
		$user = $this->getUser();
		$dirs = array(f_util_FileUtils::buildOverridePath(), 
			f_util_FileUtils::buildProjectPath("cache", "project"), 
			f_util_FileUtils::buildProjectPath("cache", "www"), 
			f_util_FileUtils::buildProjectPath("media"), 
			f_util_FileUtils::buildProjectPath("securemedia"));
		
		foreach ($dirs as $dir)
		{
			$this->message("Apply '$dir' dir policy");
			try 
			{
				// Be sure the folder is here
				f_util_FileUtils::mkdir($dir);
				f_util_FileUtils::chown($dir, $user, $apacheGroup, true);
				// Strange behaviour when SGID on files: unable to write "directly", so use
				// different mode for files
				f_util_FileUtils::chmod($dir, "2775", true, "775");
			}
			catch (Exception $e)
			{
				$this->warnMessage("WARN on Apply '$dir' dir policy: " . $e->getMessage());
			}
		}

		foreach (glob(PROJECT_HOME."/*.php") as $phpFile)
		{
			f_util_FileUtils::chmod($phpFile, "755");
		}
		
		$this->quitOk("Webapp files policy applied");
	}
}

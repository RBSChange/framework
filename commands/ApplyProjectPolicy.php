<?php
class commands_ApplyProjectPolicy extends c_ChangescriptCommand
{
	/**
	 * @return boolean
	 */
	public function isHidden()
	{
		return true;
	}

	/**
	 * @return string
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
	 * @return string
	 */
	function getDescription()
	{
		return "Apply project policy: ownership & permissions";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Apply project policy ==");
		
		$user = $this->getAuthor();
		
		$readWriteDirs = array("cache", "build", "log", "mailbox", "modules", "themes"); 
		foreach ($readWriteDirs as $dir)
		{
			$this->message("Apply '$dir' dir policy");
			try 
			{
				// Strange behaviour when SGID on files: unable to write "directly", so use
				// different mode for files
				f_util_FileUtils::chmod($dir, "2775", true, "775");
			}
			catch (Exception $e)
			{
				$this->warnMessage("Warn on Apply '$dir' dir policy: " . $e->getMessage());
			}
		}
		
		$this->quitOk("Project policy files applied");
	}
}

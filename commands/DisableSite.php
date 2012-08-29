<?php
class commands_DisableSite extends c_ChangescriptCommand
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
		return "ds";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "disable site";
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Disable site ==");
		$this->loadFramework();
		
		$flagFile = "site_is_disabled"; 
		if (file_exists($flagFile))
		{
			return $this->quitWarn("Site is already disabled");
		}
		if (!touch($flagFile))
		{
			return $this->quitError("Unable to touch $flagFile");
		}
		
		return $this->quitOk("Site has been disabled. To enable it, use '" . CHANGE_COMMAND . " enable-site'.");
	}
}
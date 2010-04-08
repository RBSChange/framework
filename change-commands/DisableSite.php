<?php
class commands_DisableSite extends commands_AbstractChangeCommand
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
		return "ds";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "disable site";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Disable site ==");
		
		$flagFile = "site_is_disabled"; 
		if (file_exists($flagFile))
		{
			return $this->quitWarn("Site is already disabled");
		}
		if (!touch($flagFile))
		{
			return $this->quitError("Unable to touch $flagFile");
		}
		
		$this->quitOk("Site has been disabled. To enable it, use 'change.php enable-site'.");
	}
}
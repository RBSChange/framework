<?php
class commands_ApplyHotfix extends commands_CheckHotfix
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Deprecated command";
	}
	
	/**
	 * @return boolean
	 */
	public function isHidden()
	{
		return true;
	}


	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		return $this->quitOk("Deprecated command");
	}
}
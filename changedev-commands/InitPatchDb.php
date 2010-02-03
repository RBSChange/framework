<?php
class commands_InitPatchDb extends commands_AbstractChangeCommand
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
		return "ipdb";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "init patch DB";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Init patch DB ==");
		$this->loadFramework();

		PatchService::getInstance()->updateRepository();
		
		$this->quitOk('Patch repository successfully updated');
	}
}
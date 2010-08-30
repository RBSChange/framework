<?php
class commands_ClearDocumentscache extends commands_AbstractChangeCommand
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
		return "cdocc";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "clear documents cache";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Clear documents cache ==");
		$this->loadFramework();
		f_persistentdocument_CacheService::getInstance()->clear();
		$this->quitOk("Documents cache cleared");
	}
}
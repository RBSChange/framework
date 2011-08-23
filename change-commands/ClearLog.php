<?php
class commands_ClearLog extends commands_AbstractChangeCommand
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
		return "cl";
	}
	
	/**
	 * @return String
	 */
	function getDescription()
	{
		return "clear log files";
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Clear log ==");	
		$logProfileDirectory = implode(DIRECTORY_SEPARATOR, array(PROJECT_HOME, 'log', 'project'));
		
		if (is_dir($logProfileDirectory))
		{
			$listOfFiles = scandir($logProfileDirectory);
			foreach ($listOfFiles as $file)
			{
				if (!is_dir($logProfileDirectory . DIRECTORY_SEPARATOR . $file))
				{
					unlink($logProfileDirectory . DIRECTORY_SEPARATOR . $file);
				}
			}
		}
		return $this->quitOk("Log files cleared");
	}
}
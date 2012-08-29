<?php
class commands_ClearLog extends c_ChangescriptCommand
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
		return "cl";
	}
	
	/**
	 * @return string
	 */
	function getDescription()
	{
		return "clear log files";
	}
	
	/**
	 * @param string[] $params
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
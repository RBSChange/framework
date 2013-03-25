<?php
class commands_ClearLog extends commands_AbstractChangeCommand
{
	/**
	 * @return string
	 */
	public function getUsage()
	{
		return "[<loggerName>]";
	}
	
	/**
	 * @return string
	 */
	public function getAlias()
	{
		return "cl";
	}
	
	/**
	 * @return string
	 */
	public function getDescription()
	{
		return "Clear log files for the sepcified logger. If no logger specified, the default one (profile) is cleared.";
	}
	
	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param string[] $params
	 * @param array<string, string> $options where the option array key is the option name, the potential option value or true
	 * @return string[] or null
	 */
	public function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 0)
		{
			$components = array();
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'log', RecursiveDirectoryIterator::KEY_AS_PATHNAME), RecursiveIteratorIterator::SELF_FIRST) as $file => $info)
			{
				/* @var $info SplFileInfo */
				if ($info->isDir() && $info->getFilename() != '.' && $info->getFilename() != '..')
				{
					$components[] = $info->getFilename();
				}
			}
			return $components;
		}
		return null;
	}
	
	/**
	 * @param string[] $params
	 * @param array<string, string> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		$this->message("== Clear log ==");
		
		$logger = isset($params[0]) ? $params[0] : $this->getProfile();
		$profile = $this->getProfile();
		if (!defined('WEBEDIT_HOME'))
		{
			define('WEBEDIT_HOME', realpath('.'));
		}
		
		// Test if the log directory of selected profile exist.
		$logProfileDirectory = WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . $logger;
		if (is_dir($logProfileDirectory))
		{
			foreach (scandir($logProfileDirectory) as $file)
			{
				if (!is_dir($logProfileDirectory . DIRECTORY_SEPARATOR . $file))
				{
					unlink($logProfileDirectory . DIRECTORY_SEPARATOR . $file);
				}
			}
		}
		else
		{
			return $this->quitError('No folder for logger "' . $logger . '".');
		}
		return $this->quitOk('Log files cleared for logger "' . $logger . '".');
	}
}
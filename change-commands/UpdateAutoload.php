<?php
class commands_UpdateAutoload extends commands_AbstractChangeCommand
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
		return "ua";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "update autoload";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Update autoload ==");
		$this->loadFramework();
		
		if (f_util_ArrayUtils::isEmpty($params))
		{
			$this->message("Scanning all the project. Please wait: this can be long.");
			ClassResolver::getInstance()->update();
		}
		else
		{
			foreach ($params as $param)
			{
				$path = realpath($param);
				if ($path === false)
				{
					$this->errorMessage("Could not resolve $param as file, ignoring");
					continue;
				}
				if (is_dir($path))
				{
					$this->message("Adding $path directory to autoload");
					ClassResolver::getInstance()->appendDir($path, true);
					continue;
				}
				$this->message("Adding $path file to autoload");
				ClassResolver::getInstance()->appendFile($path, true);
			}
		}
		
		$this->getParent()->executeCommand("compile-aop");
		
		if ($this->hasError())
		{
			return $this->quitError("Some errors: ".$this->getErrorCount());
		}
		return $this->quitOk("Autoload updated");
	}
}
<?php
class commands_CompileAutoload extends c_ChangescriptCommand
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
		return "compile autoload";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile autoload ==");		
		if (f_util_ArrayUtils::isEmpty($params))
		{
			$this->message("Scanning all the project. Please wait: this can be long.");
			change_AutoloadBuilder::getInstance()->update();
		}
		else
		{
			foreach ($params as $param)
			{
				$path = PROJECT_HOME . DIRECTORY_SEPARATOR . $param;
				if (!file_exists($path))
				{
					$this->errorMessage("Could not resolve $param as file, ignoring");
					continue;
				}
				if (is_dir($path))
				{
					$this->message("Adding $path directory to autoload");
					change_AutoloadBuilder::getInstance()->appendDir($path);
					continue;
				}
				$this->message("Adding $path file to autoload");
				change_AutoloadBuilder::getInstance()->appendFile($path);
			}
		}
		
		if ($this->hasError())
		{
			return $this->quitError("Some errors: ".$this->getErrorCount());
		}
		return $this->quitOk("Autoload compiled");
	}
}
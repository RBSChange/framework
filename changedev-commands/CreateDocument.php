<?php
class commands_CreateDocument extends commands_AbstractChangedevCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<moduleName> <name>";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "initialize a document";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		return count($params) == 2;
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options)
	{
		if ($completeParamCount == 0)
		{
			$components = array();
			foreach (glob("modules/*", GLOB_ONLYDIR) as $module)
			{
				$components[] = basename($module);
			}
			return $components;
		}
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Create document ==");

		$moduleName = $params[0];
		$documentName = $params[1];
		
		$this->loadFramework();
		$to = f_util_FileUtils::buildWebeditPath("modules", $moduleName, "persistentdocument", "$documentName.xml");
		if (file_exists($to))
		{
			return $this->quitError("Document $moduleName/$documentName already exists (check $to)");
		}
		
		$from = f_util_FileUtils::buildFrameworkPath("builder", "resources", "base-document.xml");
		f_util_FileUtils::cp($from, $to);
			
		$this->quitOk("Document $moduleName/$documentName initialized.
You must now edit $to and later call add-document");
	}
}
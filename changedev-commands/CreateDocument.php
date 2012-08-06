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
		if (count($params) != 2)
		{
			return false;
		}
		$moduleName = strtolower($params[0]);
		if (!file_exists(f_util_FileUtils::buildWebeditPath('modules', $moduleName)))
		{
			$this->errorMessage('Module "' . $moduleName . '" not found.');
			return false;
		}
		$documentName = strtolower($params[1]);
		if (file_exists(f_util_FileUtils::buildWebeditPath('modules', $moduleName, 'persistentdocument', $documentName . '.xml')))
		{
			$this->errorMessage('Document "' . $moduleName . '/' .$documentName. '" already exist.');
			return false;
		}
		if (!preg_match('/^[a-z0-9]+$/', $documentName))
		{
			$this->errorMessage('Name "' . $documentName . '" is not valid for a document');
			return false;
		}
		return true;
	}

	/**
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 0)
		{
			$components = array();
			$baseDir = f_util_FileUtils::buildWebeditPath('modules', '*');
			foreach (glob($baseDir, GLOB_ONLYDIR) as $module)
			{
				$components[] = basename($module);
			}
			return $components;
		}
		return null;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
	
		$moduleName = strtolower($params[0]);
		$documentName = strtolower($params[1]);
		
		$this->message("== Create document ==");
		
		$this->loadFramework();
		$to = f_util_FileUtils::buildWebeditPath("modules", $moduleName, "persistentdocument", "$documentName.xml");		
		$from = f_util_FileUtils::buildFrameworkPath("builder", "resources", "base-document.xml");
		f_util_FileUtils::cp($from, $to);
			
		return $this->quitOk("Document $moduleName/$documentName initialized.
You must now edit $to and later call add-document");
	}
}
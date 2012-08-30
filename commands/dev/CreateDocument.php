<?php
class commands_CreateDocument extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "<moduleName> <name>";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "initialize a document";
	}

	/**
	 * @param integer $completeParamCount the parameters that are already complete in the command line
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return string[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		if ($completeParamCount == 0)
		{
			$components = array();
			foreach ($this->getBootStrap()->getProjectDependencies() as $package)
			{
				/* @var $package c_Package */
				if ($package->isModule())
				{
					$components[] = $package->getName();
				}
			}
			return $components;
		}
		return null;
	}
	
	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		if (count($params) != 2)
		{
			return false;
		}
		$moduleName = strtolower($params[0]);
		if (!file_exists(f_util_FileUtils::buildProjectPath('modules', $moduleName)))
		{
			$this->errorMessage('Module "' . $moduleName . '" not found.');
			return false;
		}
		$documentName = strtolower($params[1]);
		if (file_exists(f_util_FileUtils::buildProjectPath('modules', $moduleName, 'persistentdocument', $documentName . '.xml')))
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
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		$this->message("== Create document ==");
		$moduleName = strtolower($params[0]);
		$documentName = strtolower($params[1]);
		
		$this->loadFramework();
		$to = f_util_FileUtils::buildModulesPath($moduleName, "persistentdocument", "$documentName.xml");
		$from = f_util_FileUtils::buildFrameworkPath("builder", "templates", "documents", "base-document.xml");
		f_util_FileUtils::cp($from, $to);
			
		$this->log("You must now edit $to and later call add-document.");
		return $this->quitOk("Document $moduleName/$documentName initialized.");
	}
}
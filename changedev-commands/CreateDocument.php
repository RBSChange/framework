<?php
class commands_CreateDocument extends c_ChangescriptCommand
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
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 */
	protected function validateArgs($params, $options)
	{
		if (count($params) == 2)
		{
			$package = $this->getPackageByName($params[0]);
			if ($package->isModule() && $package->isInProject())
			{
				if (preg_match('/^[a-z][a-z0-9]{1,49}+$/', $params[1]))
				{
					return true;
				}
				else
				{
					$this->errorMessage('Invalid document name: ' . $params[1]);
				}
			}
			else
			{
				$this->errorMessage('Invalid module name: ' . $params[0]);
			}
		}
		return false;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Create document ==");
		$package = $this->getPackageByName($params[0]);
		$moduleName = $package->getName();
		$documentName = $params[1];
		
		$this->loadFramework();
		$to = f_util_FileUtils::buildModulesPath($moduleName, "persistentdocument", "$documentName.xml");
		if (file_exists($to))
		{
			return $this->quitError("Document $moduleName/$documentName already exists (check $to)");
		}
		
		$from = f_util_FileUtils::buildFrameworkPath("builder", "resources", "base-document.xml");
		f_util_FileUtils::cp($from, $to);
			
		$this->log("You must now edit $to and later call add-document.");
		return $this->quitOk("Document $moduleName/$documentName initialized.");
	}
}